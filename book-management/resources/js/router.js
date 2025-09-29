import { createRouter, createWebHistory } from 'vue-router';
import BookIndex from '@/components/BookIndex.vue';
import BookCreate from '@/components/BookCreate.vue';
import BookEdit from '@/components/BookEdit.vue';
import StudentIndex from '@/pages/StudentIndex.vue';
import BorrowCreate from '@/pages/BorrowCreate.vue';
import Home from '@/pages/Home.vue';
import BookRequests from '@/pages/BookRequests/Index.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home
    },
    {
        path: '/books',
        name: 'books.index',
        component: BookIndex
    },
    {
        path: '/books/create',
        name: 'books.create',
        component: BookCreate
    },
    {
        path: '/books/:id/edit',
        name: 'books.edit',
        component: BookEdit
    },
    {
        path: '/students',
        name: 'students.index',
        component: StudentIndex
    },
    {
        path: '/borrows/create',
        name: 'borrows.create',
        component: BorrowCreate
    },
    {
        path: '/book-requests',
        name: 'book-requests.index',
        component: BookRequests
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
