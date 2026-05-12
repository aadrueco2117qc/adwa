 1. — Remove voice-to-text and "0" badge (quick wins, no dependencies)
 1.1 Remove #btnVoice button and "Text or voice" subtitle from view.view.php Notes tab
 1.2 Remove els.btnVoice, voice event listener, voiceRec, and all SpeechRecognition code from workorder.js
 1.3 Remove the notes count "0" badge element from the Saved Notes panel header in view.view.php
 1.4 Remove any notes-count badge update code from renderNotes() in workorder.js
 
 2. — Resolved job view (index cards + detail page banner)
 2.1 In index.view.php, change resolved/closed card button to "View Job" with grey styling
 2.2 In view.view.php, add a "COMPLETED" green banner below the breadcrumb for resolved/closed WOs
 
 3. — Start Work Gate (visual lock banners per tab)
 3.1 Add #gateNotice banner HTML inside each tab pane in view.view.php
 3.2 In workorder.js, show/hide gate banners based on isEditableNow in applyReadOnlyState()
 3.3 Verify startWork() correctly calls setTechnicianEditable(true) and hides banners
 