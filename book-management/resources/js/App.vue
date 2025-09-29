<template>
  <div id="app" class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-6xl mx-auto">
      <header class="bg-white rounded-lg shadow p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">蔵書管理システム</h1>
        <p class="text-gray-600 mt-2">Phase 3 - コアコンポーネント実装完了</p>
      </header>
      
      <nav class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex space-x-6">
          <router-link to="/books" class="text-blue-600 hover:text-blue-800 font-medium">書籍一覧</router-link>
          <router-link to="/books/create" class="text-blue-600 hover:text-blue-800 font-medium">書籍登録</router-link>
          <router-link to="/students" class="text-blue-600 hover:text-blue-800 font-medium">生徒一覧</router-link>
          <router-link to="/borrows/create" class="text-blue-600 hover:text-blue-800 font-medium">貸出登録</router-link>
          <router-link to="/book-requests" class="text-blue-600 hover:text-blue-800 font-medium">本のリクエスト</router-link>
        </div>
      </nav>
      
      <main class="bg-white rounded-lg shadow p-6">
        <router-view />
      </main>
      
      <footer class="text-center mt-6 text-gray-500">
        <p>&copy; 2025 蔵書管理システム</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
// シンプルバージョン - 外部コンポーネントのインポートなし
console.log('App.vue loaded successfully');
</script>