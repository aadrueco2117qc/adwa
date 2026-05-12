/* IndexedDB storage for Blobs (images, videos, config files)
   Replaces Base64 dataURLs in localStorage to avoid 5MB limit
   API: idbStorage.saveBlobAndGetId(), idbStorage.getBlobAsUrl(), idbStorage.deleteBlob(), etc.
*/
(function () {
  const DB_NAME = 'mrtsp_technician';
  const DB_VERSION = 1;
  const STORE_NAME = 'blobs';

  let db = null;
  let initPromise = null;

  function initDB() {
    if (db) return Promise.resolve(db);
    if (initPromise) return initPromise;

    initPromise = new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      
      req.onerror = () => reject(new Error('IndexedDB open failed'));
      
      req.onsuccess = () => {
        db = req.result;
        resolve(db);
      };

      req.onupgradeneeded = (e) => {
        const database = e.target.result;
        if (!database.objectStoreNames.contains(STORE_NAME)) {
          const store = database.createObjectStore(STORE_NAME, { keyPath: 'id' });
          store.createIndex('woId', 'woId', { unique: false });
          store.createIndex('createdAt', 'createdAt', { unique: false });
        }
      };
    });

    return initPromise;
  }

  // File extension validation rules by category
  const FILE_RULES = {
    config: {
      maxSize: 50 * 1024 * 1024, // 50MB
      extensions: ['.json', '.xml', '.cfg', '.conf', '.ini', '.txt'],
      mimeTypes: ['application/json', 'text/xml', 'application/xml', 'text/plain', 'application/octet-stream'],
      label: 'Config files'
    },
    log: {
      maxSize: 50 * 1024 * 1024, // 50MB
      extensions: ['.log', '.txt', '.csv'],
      mimeTypes: ['text/plain', 'text/csv', 'application/octet-stream'],
      label: 'Log files'
    },
    backup: {
      maxSize: 50 * 1024 * 1024, // 50MB
      extensions: ['.zip', '.tar', '.gz', '.bak', '.img'],
      mimeTypes: ['application/zip', 'application/x-tar', 'application/gzip', 'application/x-gzip', 'application/octet-stream'],
      label: 'Backup files'
    },
    image: {
      maxSize: 20 * 1024 * 1024, // 20MB
      extensions: ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
      mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
      label: 'Images'
    },
    video: {
      maxSize: 100 * 1024 * 1024, // 100MB
      extensions: ['.mp4', '.webm', '.mov'],
      mimeTypes: ['video/mp4', 'video/webm', 'video/quicktime'],
      label: 'Videos'
    }
  };

  // Get file extension from filename
  function getFileExtension(filename) {
    if (!filename) return '';
    const lastDot = filename.lastIndexOf('.');
    if (lastDot === -1) return '';
    return filename.substring(lastDot).toLowerCase();
  }

  // Detect file category from extension
  function detectFileCategory(filename) {
    const ext = getFileExtension(filename);
    if (!ext) return null;
    
    for (const [category, rules] of Object.entries(FILE_RULES)) {
      if (rules.extensions.includes(ext)) {
        return category;
      }
    }
    return null;
  }

  // Get all allowed extensions for configuration uploads (config + log + backup)
  function getAllowedConfigExtensions() {
    return [
      ...FILE_RULES.config.extensions,
      ...FILE_RULES.log.extensions,
      ...FILE_RULES.backup.extensions
    ].filter((v, i, a) => a.indexOf(v) === i); // unique
  }

  // Validate file type and size
  // For config files, we're lenient - just check size (HTML input already restricts types)
  function validateFile(file, fileType = 'image') {
    // Config files: only check size limit, skip extension/MIME validation
    // The HTML file input's accept attribute already limits file types
    if (fileType === 'config') {
      const maxSize = 50 * 1024 * 1024; // 50MB
      if (file.size > maxSize) {
        throw new Error('File exceeds maximum size of 50MB');
      }
      return { valid: true, category: 'config' };
    }
    
    // For images/videos, do full validation
    const rules = FILE_RULES[fileType];
    if (!rules) {
      throw new Error(`Unknown file type: ${fileType}`);
    }
    
    // Check file size
    if (file.size > rules.maxSize) {
      const maxMB = Math.round(rules.maxSize / 1024 / 1024);
      throw new Error(`File exceeds maximum size of ${maxMB}MB`);
    }
    
    // Check MIME type for images/videos
    if (!rules.mimeTypes.includes(file.type)) {
      throw new Error(`Invalid file type. Allowed: ${rules.mimeTypes.join(', ')}`);
    }

    return { valid: true, category: fileType };
  }

  async function saveBlobAndGetId(woId, file, fileType = 'image') {
    // Validate file first
    validateFile(file, fileType);

    const database = await initDB();
    const id = `blob_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    
    const record = {
      id,
      woId,
      fileName: file.name,
      mimeType: file.type,
      size: file.size,
      blob: file,
      fileType,
      createdAt: Date.now(),
    };

    return new Promise((resolve, reject) => {
      const tx = database.transaction([STORE_NAME], 'readwrite');
      const store = tx.objectStore(STORE_NAME);
      const req = store.add(record);

      req.onsuccess = () => resolve(id);
      req.onerror = () => reject(new Error('Failed to save blob'));
    });
  }

  async function getBlob(blobId) {
    const database = await initDB();
    return new Promise((resolve, reject) => {
      const tx = database.transaction([STORE_NAME], 'readonly');
      const store = tx.objectStore(STORE_NAME);
      const req = store.get(blobId);

      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(new Error('Failed to get blob'));
    });
  }

  async function deleteBlob(blobId) {
    const database = await initDB();
    return new Promise((resolve, reject) => {
      const tx = database.transaction([STORE_NAME], 'readwrite');
      const store = tx.objectStore(STORE_NAME);
      const req = store.delete(blobId);

      req.onsuccess = () => resolve();
      req.onerror = () => reject(new Error('Failed to delete blob'));
    });
  }

  async function getBlobAsUrl(blobId) {
    try {
      const record = await getBlob(blobId);
      if (!record || !record.blob) return null;
      return URL.createObjectURL(record.blob);
    } catch (e) {
      return null;
    }
  }

  async function deleteWorkOrderBlobs(woId) {
    const database = await initDB();
    return new Promise((resolve, reject) => {
      const tx = database.transaction([STORE_NAME], 'readwrite');
      const store = tx.objectStore(STORE_NAME);
      const index = store.index('woId');
      const req = index.getAll(woId);

      req.onsuccess = () => {
        const records = req.result;
        const deleteTx = database.transaction([STORE_NAME], 'readwrite');
        const deleteStore = deleteTx.objectStore(STORE_NAME);
        records.forEach((rec) => deleteStore.delete(rec.id));

        deleteTx.oncomplete = () => resolve();
        deleteTx.onerror = () => reject(new Error('Failed to delete WO blobs'));
      };
      req.onerror = () => reject(new Error('Failed to query blobs'));
    });
  }

  async function getAllBlobIds(woId) {
    const database = await initDB();
    return new Promise((resolve, reject) => {
      const tx = database.transaction([STORE_NAME], 'readonly');
      const store = tx.objectStore(STORE_NAME);
      const index = store.index('woId');
      const req = index.getAll(woId);

      req.onsuccess = () => {
        const ids = req.result.map((rec) => rec.id);
        resolve(ids);
      };
      req.onerror = () => reject(new Error('Failed to get blob IDs'));
    });
  }

  // Backward compatibility: migrate old Base64 dataURLs to IndexedDB
  async function migrateDataUrlToBlob(dataUrl, woId) {
    try {
      if (!dataUrl || !dataUrl.startsWith('data:')) return null;

      const [header, data] = dataUrl.split(',');
      const mimeMatch = header.match(/data:([^;]+)/);
      const mimeType = mimeMatch ? mimeMatch[1] : 'application/octet-stream';

      const binaryStr = atob(data);
      const bytes = new Uint8Array(binaryStr.length);
      for (let i = 0; i < binaryStr.length; i++) {
        bytes[i] = binaryStr.charCodeAt(i);
      }

      const blob = new Blob([bytes], { type: mimeType });
      const file = new File([blob], 'migrated-file', { type: mimeType });

      return await saveBlobAndGetId(woId, file);
    } catch (e) {
      console.error('[v0] Migration failed:', e);
      return null;
    }
  }

  window.MRTS = window.MRTS || {};
  window.MRTS.idbStorage = {
    initDB,
    validateFile,
    saveBlobAndGetId,
    getBlob,
    deleteBlob,
    getBlobAsUrl,
    deleteWorkOrderBlobs,
    getAllBlobIds,
    migrateDataUrlToBlob,
    // Expose helpers for UI
    FILE_RULES,
    getFileExtension,
    detectFileCategory,
    getAllowedConfigExtensions,
  };
})();
