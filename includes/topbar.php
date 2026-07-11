<nav class="bg-neutral-primary fixed w-full z-20 top-0 start-0 border-b border-default">
  <div class="flex flex-wrap items-center justify-between mx-auto px-4 h-16">
    <div class="flex items-center gap-3">
      <button data-drawer-toggle="default-sidebar" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-body rounded-base hover:bg-neutral-secondary-soft hover:text-heading focus:outline-none focus:ring-2 focus:ring-neutral-tertiary sm:hidden">
        <span class="sr-only">Open sidebar</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
      </button>
      <a href="<?= BASE_URL ?>/admin/dashboard" class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center">
          <span class="text-white text-xs font-bold">H</span>
        </div>
        <span class="text-xl text-heading font-semibold">HRMS Core</span>
      </a>
    </div>
    <div class="flex items-center space-x-3">
      <button type="button" class="flex text-sm bg-neutral-primary rounded-full focus:ring-4 focus:ring-neutral-tertiary" id="user-menu-button" onclick="toggleDropdown()">
        <span class="sr-only">Open user menu</span>
        <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container text-xs font-bold">
          <?= strtoupper(substr($_SESSION['user_name'] ?? 'AU', 0, 2)) ?>
        </div>
      </button>
      <div class="z-50 hidden bg-neutral-primary-medium border border-default-medium rounded-base shadow-lg w-44 absolute top-14 right-4" id="user-dropdown">
        <div class="px-4 py-3 text-sm border-b border-default">
          <span class="block text-heading font-medium"><?= h($_SESSION['user_name'] ?? 'Admin User') ?></span>
          <span class="block text-body truncate"><?= h($_SESSION['role'] ?? 'User') ?></span>
        </div>
        <ul class="p-2 text-sm text-body font-medium">
          <li>
            <a href="<?= BASE_URL ?>/logout" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Sign out</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<script>
function toggleDropdown() {
  var dd = document.getElementById('user-dropdown');
  dd.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  var dd = document.getElementById('user-dropdown');
  var btn = document.getElementById('user-menu-button');
  if (!dd.contains(e.target) && !btn.contains(e.target)) {
    dd.classList.add('hidden');
  }
});
</script>
