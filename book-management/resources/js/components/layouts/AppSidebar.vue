<template>
  <aside class="fixed left-0 top-16 h-full w-64 bg-white shadow-sm border-r border-gray-200 z-40">
    <nav class="p-6">
      <!-- メインナビゲーション -->
      <div class="space-y-8">
        <!-- 書籍管理 -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">書籍管理</h3>
          <ul class="space-y-2">
            <li>
              <router-link
                to="/books"
                class="nav-link"
                :class="{ 'nav-link-active': $route.path === '/books' }"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                書籍一覧
              </router-link>
            </li>
            <li>
              <router-link
                to="/books/create"
                class="nav-link"
                :class="{ 'nav-link-active': $route.path === '/books/create' }"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                新規登録
              </router-link>
            </li>
          </ul>
        </div>

        <!-- 統計・レポート -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">統計・レポート</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                統計ダッシュボード
              </a>
            </li>
            <li>
              <a href="#" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                読書進捗
              </a>
            </li>
          </ul>
        </div>

        <!-- カテゴリ・タグ -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">カテゴリ・タグ</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                タグ管理
              </a>
            </li>
            <li>
              <a href="#" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                カテゴリ管理
              </a>
            </li>
          </ul>
        </div>

        <!-- 設定 -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">設定</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                システム設定
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </aside>
</template>

<style scoped>
.nav-link {
  @apply flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors;
}

.nav-link-active {
  @apply bg-blue-50 text-blue-700 border-r-2 border-blue-600;
}
</style>
