<!-- Back row -->
<div class="back-row mb-4">
  <a href="<?= $back_url ?>">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to User Access Control
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700"><?= $page_heading ?></span>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-err mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Please fix the errors below before saving.</span>
</div>
<?php endif; ?>

<form method="POST" action="save.php" novalidate>
  <input type="hidden" name="user_id"    value="<?= $edit_id ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>">

  <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-4 items-start">

    <!-- LEFT: main fields -->
    <div class="flex flex-col gap-4">

      <!-- Identity -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="sdiv">Identity</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

          <div class="md:col-span-2">
            <label class="flbl" for="full_name">Full Name <span class="text-red-500">*</span></label>
            <input type="text" id="full_name" name="full_name"
                   value="<?= htmlspecialchars($v('full_name')) ?>"
                   class="fin <?= isset($errors['full_name']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. Juan dela Cruz" autocomplete="name">
            <?php if (isset($errors['full_name'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['full_name']) ?></p>
            <?php endif; ?>
          </div>

          <div>
            <label class="flbl" for="id_number">ID Number</label>
            <input type="text" id="id_number" name="id_number"
                   value="<?= htmlspecialchars($v('id_number')) ?>"
                   class="fin <?= isset($errors['id_number']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. 2021-12345">
            <?php if (isset($errors['id_number'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['id_number']) ?></p>
            <?php else: ?>
              <p class="fhint">Student number or employee ID.</p>
            <?php endif; ?>
          </div>

          <div>
            <label class="flbl" for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number"
                   value="<?= htmlspecialchars($v('contact_number')) ?>"
                   class="fin"
                   placeholder="e.g. 09171234567">
          </div>

          <div>
            <label class="flbl" for="position">Position / Title</label>
            <input type="text" id="position" name="position"
                   value="<?= htmlspecialchars($v('position')) ?>"
                   class="fin"
                   placeholder="e.g. Lab Instructor">
          </div>

          <div>
            <label class="flbl" for="department_id">Department</label>
            <select id="department_id" name="department_id" class="fsel">
              <option value="">— None —</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['department_id'] ?>"
                  <?= (string)$v('department_id') === (string)$d['department_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($d['department_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>
      </div>

      <!-- Login credentials -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="sdiv">Login Credentials</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

          <div class="md:col-span-2">
            <label class="flbl" for="email">Email Address <span class="text-red-500">*</span></label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($v('email')) ?>"
                   class="fin <?= isset($errors['email']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. juan.delacruz@olfu.edu.ph" autocomplete="email">
            <?php if (isset($errors['email'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['email']) ?></p>
            <?php endif; ?>
          </div>

          <div>
            <label class="flbl" for="password">
              Password <?= $is_edit ? '' : '<span class="text-red-500">*</span>' ?>
            </label>
            <input type="password" id="password" name="password"
                   class="fin <?= isset($errors['password']) ? 'fin-err' : '' ?>"
                   placeholder="<?= $is_edit ? 'Leave blank to keep current password' : 'Minimum 8 characters' ?>"
                   autocomplete="new-password">
            <?php if (isset($errors['password'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['password']) ?></p>
            <?php elseif (!$is_edit): ?>
              <p class="fhint">Minimum 8 characters.</p>
            <?php endif; ?>
          </div>

          <div>
            <label class="flbl" for="confirm_password">
              Confirm Password <?= $is_edit ? '' : '<span class="text-red-500">*</span>' ?>
            </label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="fin <?= isset($errors['confirm_password']) ? 'fin-err' : '' ?>"
                   placeholder="Re-enter password"
                   autocomplete="new-password">
            <?php if (isset($errors['confirm_password'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['confirm_password']) ?></p>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div><!-- end left -->

    <!-- RIGHT: role & status -->
    <div class="flex flex-col gap-4">

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="sdiv">Access Control</div>
        <div class="space-y-4">

          <div>
            <label class="flbl" for="role_id">Role <span class="text-red-500">*</span></label>
            <select id="role_id" name="role_id" class="fsel <?= isset($errors['role_id']) ? 'fsel-err' : '' ?>">
              <option value="">— Select role —</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['role_id'] ?>"
                  <?= (string)$v('role_id') === (string)$r['role_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars(role_display_name($r['role_name'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['role_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['role_id']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Role descriptions -->
          <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500 space-y-1.5">
            <div><strong class="text-gray-700">Admin</strong> — Full system access</div>
            <div><strong class="text-gray-700">IT Manager</strong> — Assets, tickets, work orders, reports</div>
            <div><strong class="text-gray-700">IT Staff</strong> — Assets and tickets</div>
            <div><strong class="text-gray-700">Technician</strong> — Technician ops and tickets</div>
            <div><strong class="text-gray-700">Faculty / Dept. Staff / Student</strong> — Submit tickets only</div>
          </div>

        </div>
      </div>

      <!-- Save -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-3">
        <button type="submit"
          class="w-full inline-flex items-center justify-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors duration-150">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <?= $is_edit ? 'Save Changes' : 'Create User' ?>
        </button>
        <a href="<?= $back_url ?>"
           class="w-full inline-flex items-center justify-center text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2.5 rounded-lg transition-colors duration-150">
          Cancel
        </a>
      </div>

    </div><!-- end right -->

  </div><!-- end grid -->
</form>
