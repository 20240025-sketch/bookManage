import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'

// ページコンポーネント - 動的インポートを使用
const BookIndex = () => import('../pages/BookIndex.vue')
const BookCreate = () => import('../pages/BookCreate.vue')
const NoIsbnBookCreate = () => import('../pages/NoIsbnBookCreate.vue')
const BookShow = () => import('../pages/BookShow.vue')
const BookEdit = () => import('../pages/BookEdit.vue')
const StudentIndex = () => import('../pages/StudentIndex.vue')
const BorrowCreate = () => import('../pages/BorrowCreate.vue')
const BorrowStatus = () => import('../pages/BorrowStatus.vue')
const UsageStatistics = () => import('../pages/UsageStatistics.vue')
const LibraryDuty = () => import('../pages/LibraryDuty.vue')
const BookRequestsIndex = () => import('../pages/BookRequests/Index.vue')
const Notifications = () => import('../pages/Notifications.vue')
const Login = () => import('../pages/Login.vue')
const AdminLogin = () => import('../pages/AdminLogin.vue')
const PasswordSetup = () => import('../pages/PasswordSetup.vue')
const PasswordChange = () => import('../pages/PasswordChange.vue')

const routes = [
  {
    path: '/',
    redirect: '/books'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { title: 'ログイン', requiresGuest: true }
  },
  {
    path: '/admin-login',
    name: 'AdminLogin',
    component: AdminLogin,
    meta: { title: '管理者ログイン', requiresGuest: true }
  },
  {
    path: '/password-setup',
    name: 'PasswordSetup',
    component: PasswordSetup,
    meta: { title: 'パスワード設定', requiresGuest: true }
  },
  {
    path: '/password-change',
    name: 'PasswordChange',
    component: PasswordChange,
    meta: { title: 'パスワード変更', requiresAuth: true }
  },
  {
    path: '/books',
    name: 'BookIndex',
    component: BookIndex,
    meta: { title: '書籍一覧', requiresAuth: true }
  },
  {
    path: '/books/create',
    name: 'BookCreate',
    component: BookCreate,
    meta: { title: '書籍登録', requiresAuth: true }
  },
  {
    path: '/books/create-no-isbn',
    name: 'NoIsbnBookCreate',
    component: NoIsbnBookCreate,
    meta: { title: '独自コード書籍登録', requiresAuth: true }
  },
  {
    path: '/books/:id',
    name: 'BookShow',
    component: BookShow,
    meta: { title: '書籍詳細', requiresAuth: true },
    props: true
  },
  {
    path: '/books/:id/edit',
    name: 'BookEdit',
    component: BookEdit,
    meta: { title: '書籍編集', requiresAuth: true },
    props: true
  },
  {
    path: '/students',
    name: 'StudentIndex',
    component: StudentIndex,
    meta: { title: '生徒一覧', requiresAuth: true }
  },
  {
    path: '/borrows/create',
    name: 'BorrowCreate',
    component: BorrowCreate,
    meta: { title: '貸出登録', requiresAuth: true }
  },
  {
    path: '/borrow-status',
    name: 'BorrowStatus',
    component: BorrowStatus,
    meta: { title: '貸出状況', requiresAuth: true }
  },
  {
    path: '/usage-statistics',
    name: 'UsageStatistics',
    component: UsageStatistics,
    meta: { title: '利用状況', requiresAuth: true }
  },
  {
    path: '/library-duty',
    name: 'LibraryDuty',
    component: LibraryDuty,
    meta: { title: '図書当番', requiresAuth: true }
  },
  {
    path: '/book-requests',
    name: 'BookRequestsIndex',
    component: BookRequestsIndex,
    meta: { title: '本のリクエスト', requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: Notifications,
    meta: { title: '通知', requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 認証チェック関数
function isAuthenticated() {
  const student = localStorage.getItem('student')
  return !!student
}

// ページタイトル設定と認証ガード
router.beforeEach(async (to, from, next) => {
  console.log(`ルーターガード: ${from.path} -> ${to.path}`)
  
  document.title = to.meta.title ? `${to.meta.title} - 蔵書管理システム` : '蔵書管理システム'
  
  const studentData = localStorage.getItem('student')
  console.log('localStorage student データ:', studentData ? 'あり' : 'なし')
  
  // 認証が必要なページの場合
  if (to.matched.some(record => record.meta.requiresAuth)) {
    console.log('認証が必要なページです')
    if (!studentData) {
      console.log('認証データなし。ログインページにリダイレクト')
      next('/login')
      return
    }
    
    console.log('認証データあり。ページに進みます。')
    next()
  }
  // ゲスト専用ページ（ログイン・パスワード設定）の場合
  else if (to.matched.some(record => record.meta.requiresGuest)) {
    console.log('ゲスト専用ページです')
    if (studentData) {
      console.log('既にログイン済み。書籍一覧にリダイレクト')
      next('/books')
      return
    }
    console.log('未認証。ページに進みます。')
    next()
  }
  // 認証不要なページの場合
  else {
    console.log('認証不要なページです。')
    next()
  }
})

export default router
