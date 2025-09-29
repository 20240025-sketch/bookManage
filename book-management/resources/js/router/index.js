import { createRouter, createWebHistory } from 'vue-router'

// ページコンポーネント - 動的インポートを使用
const BookIndex = () => import('../pages/BookIndex.vue')
const BookCreate = () => import('../pages/BookCreate.vue')
const BookShow = () => import('../pages/BookShow.vue')
const BookEdit = () => import('../pages/BookEdit.vue')
const StudentIndex = () => import('../pages/StudentIndex.vue')
const BorrowCreate = () => import('../pages/BorrowCreate.vue')
const BookRequestsIndex = () => import('../pages/BookRequests/Index.vue')

const routes = [
  {
    path: '/',
    redirect: '/books'
  },
  {
    path: '/books',
    name: 'BookIndex',
    component: BookIndex,
    meta: { title: '書籍一覧' }
  },
  {
    path: '/books/create',
    name: 'BookCreate',
    component: BookCreate,
    meta: { title: '書籍登録' }
  },
  {
    path: '/books/:id',
    name: 'BookShow',
    component: BookShow,
    meta: { title: '書籍詳細' },
    props: true
  },
  {
    path: '/books/:id/edit',
    name: 'BookEdit',
    component: BookEdit,
    meta: { title: '書籍編集' },
    props: true
  },
  {
    path: '/students',
    name: 'StudentIndex',
    component: StudentIndex,
    meta: { title: '生徒一覧' }
  },
  {
    path: '/borrows/create',
    name: 'BorrowCreate',
    component: BorrowCreate,
    meta: { title: '貸出登録' }
  },
  {
    path: '/book-requests',
    name: 'BookRequestsIndex',
    component: BookRequestsIndex,
    meta: { title: '本のリクエスト' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// ページタイトル設定
router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - 蔵書管理システム` : '蔵書管理システム'
  next()
})

export default router
