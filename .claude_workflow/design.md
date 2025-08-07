# 蔵書管理システム 設計書（Phase 3移行版）

## 1. システム設計概要
### 1.1 アーキテクチャ
- **アプリケーション構成**: SPA（Single Page Application）
- **通信方式**: RESTful API（Laravel API + Vue.js SPA）
- **データフロー**: Vue.js → Laravel API → SQLite

### 1.2 技術スタック詳細（Phase 3実装版）
- **フロントエンド**: Vue.js 3 + Composition API + Vue Router + Pinia（状態管理）⏳ Phase 3実装予定
- **バックエンド**: Laravel 12 + Eloquent ORM + API Resources ✅ Phase 2完了
- **スタイリング**: Tailwind CSS + Heroicons（アイコン）⏳ Phase 3実装予定
- **ビルドツール**: Vite ⏳ Phase 3実装予定
- **データベース**: SQLite ✅ Phase 2完了

### 1.3 Laravel 12対応（実装済み）
- **ルーティング**: RouteServiceProvider廃止 → bootstrap/app.php直接設定 ✅
- **設定ファイル**: 新しいLaravel構造に対応 ✅
- **API有効化**: withRouting()メソッド使用 ✅

## 2. フロントエンド設計（Phase 3実装予定）

### 2.1 Vue.js 3アプリケーション構造
```
resources/js/
├── app.js                     # エントリーポイント（SPA初期化）
├── router/
│   └── index.js              # Vue Router 4設定
├── stores/
│   └── bookStore.js          # Pinia状態管理
├── composables/
│   ├── useBooks.js           # 書籍API操作
│   └── useNotifications.js   # 通知システム
├── components/
│   ├── layouts/              # レイアウトコンポーネント
│   │   ├── AppHeader.vue     # ヘッダー（ナビゲーション）
│   │   ├── AppSidebar.vue    # サイドバー（フィルター）
│   │   └── AppFooter.vue     # フッター
│   ├── ui/                   # 汎用UIコンポーネント
│   │   ├── LoadingSpinner.vue
│   │   ├── SearchBox.vue
│   │   ├── NotificationToast.vue
│   │   └── ConfirmDialog.vue
│   └── books/                # 書籍専用コンポーネント
│       ├── BookCard.vue      # 書籍カード表示
│       ├── BookList.vue      # 書籍一覧コンテナ
│       ├── BookForm.vue      # 書籍登録・編集フォーム
│       └── BookFilters.vue   # 検索・フィルター
└── pages/                    # ページコンポーネント
    ├── BookIndex.vue         # 書籍一覧ページ
    ├── BookCreate.vue        # 書籍登録ページ
    ├── BookEdit.vue          # 書籍編集ページ
    └── BookShow.vue          # 書籍詳細ページ
```

### 2.2 ルーティング設計（Vue Router 4）
```javascript
const routes = [
  {
    path: '/',
    name: 'BookIndex',
    component: () => import('@/pages/BookIndex.vue'),
    meta: { title: '書籍一覧' }
  },
  {
    path: '/books/create',
    name: 'BookCreate',
    component: () => import('@/pages/BookCreate.vue'),
    meta: { title: '書籍登録' }
  },
  {
    path: '/books/:id',
    name: 'BookShow',
    component: () => import('@/pages/BookShow.vue'),
    meta: { title: '書籍詳細' }
  },
  {
    path: '/books/:id/edit',
    name: 'BookEdit',
    component: () => import('@/pages/BookEdit.vue'),
    meta: { title: '書籍編集' }
  }
];
```

### 2.3 状態管理設計（Pinia）
```javascript
// stores/bookStore.js
export const useBookStore = defineStore('books', {
  state: () => ({
    books: [],
    currentBook: null,
    filters: {
      search: '',
      category: '',
      reading_status: ''
    },
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    },
    loading: false,
    error: null
  }),
  
  actions: {
    async fetchBooks(params = {}),
    async createBook(bookData),
    async updateBook(id, bookData),
    async deleteBook(id),
    setFilters(newFilters),
    clearError()
  }
});
```

## 3. データベース設計（実装完了）
### 3.1 テーブル設計 ✅ Phase 2完了・Phase 3拡張
#### books テーブル
```sql
CREATE TABLE books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    title_transcription VARCHAR(255) NULL,        -- タイトルのヨミ
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(255) NULL,
    published_date DATE NULL,                     -- 出版日
    isbn VARCHAR(20) NULL,
    pages INTEGER NULL,                           -- ページ数
    price DECIMAL(8,2) NULL,                     -- 価格
    ndc VARCHAR(10) NULL,                        -- 日本十進分類法（NDC10→NDC9→NDC8の順で優先取得。dc:subject[@xsi:type="dcndl:NDC10|NDC9|NDC8"] いずれか最初に見つかった値を格納）
    reading_status VARCHAR(20) NOT NULL DEFAULT 'unread',
    acceptance_date DATE NULL,                    -- 受け入れ日 ✨ 新規追加
    acceptance_type VARCHAR(255) NULL,           -- 受け入れ種別 ✨ 新規追加
    acceptance_source VARCHAR(255) NULL,         -- 受け入れ元 ✨ 新規追加
    discard VARCHAR(255) NULL,                   -- 廃棄情報 ✨ 新規追加
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.2 ReadingStatus Enum（実装完了）✅ Phase 2完了
```php
enum ReadingStatus: string
{
    case UNREAD = 'unread';
    case READING = 'reading';
    case READ = 'read';

    public function label(): string
    {
        return match($this) {
            self::UNREAD => '未読',
            self::READING => '読書中',
            self::READ => '既読',
        };
    }
}
```

## 4. API設計（実装完了）✅ Phase 2完了
### 4.1 エンドポイント一覧（動作確認済み）
| HTTP Method | URI | Description | 実装状況 |
|-------------|-----|-------------|----------|
| GET | /api/books | 書籍一覧取得（検索・フィルター・ページネーション） | ✅ 完了 |
| POST | /api/books | 書籍登録 | ✅ 完了 |
| GET | /api/books/{id} | 書籍詳細取得 | ✅ 完了 |
| PUT | /api/books/{id} | 書籍更新 | ✅ 完了 |
| DELETE | /api/books/{id} | 書籍削除 | ✅ 完了 |

### 4.2 APIレスポンス形式（確認済み・Phase 3拡張）
```json
{
    "data": [
        {
            "id": 1,
            "title": "Laravel実践入門",
            "title_transcription": "ララベルジッセンニュウモン",
            "author": "山田太郎",
            "publisher": "技術書出版",
            "published_date": "2023-01-15",
            "isbn": "978-4-123456-78-9",
            "pages": 350,
            "price": "3200.00",
            "ndc": "007.64",
            "reading_status": "read",
            "reading_status_label": "既読",
            "acceptance_date": "2025-08-01",
            "acceptance_type": "購入",
            "acceptance_source": "書店",
            "discard": null,
            "created_at": "2025-07-23T10:00:00Z",
            "updated_at": "2025-08-07T12:00:00Z"
        }
    ],
    "links": { /* ページネーションリンク */ },
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 3
    }
}
```

## 5. UI/UX設計（Phase 3実装予定）

### 5.1 レスポンシブデザイン（Tailwind CSS）
- **Mobile First**: スマートフォン優先設計
- **ブレークポイント**: 
  - Mobile: < 768px
  - Tablet: 768px - 1024px
  - Desktop: > 1024px

### 5.2 カラーパレット・デザインシステム
```css
/* Tailwind CSS カスタムカラー */
:root {
  --primary: #3B82F6;    /* blue-500 */
  --secondary: #6B7280;  /* gray-500 */
  --success: #10B981;    /* emerald-500 */
  --warning: #F59E0B;    /* amber-500 */
  --danger: #EF4444;     /* red-500 */
}
```

### 5.3 コンポーネントUI設計
#### BookCard.vue
- 書籍タイトル、著者表示
- 読書ステータスバッジ
- アクションボタン（詳細・編集・削除）
- ホバーエフェクト

#### SearchBox.vue
- リアルタイム検索（デバウンス処理）
- 検索履歴機能
- フィルタークリア機能

#### BookForm.vue
- バリデーション表示
- 入力エラーハンドリング
- 自動保存機能（下書き）

## 6. パフォーマンス設計

### 6.1 フロントエンド最適化（Phase 3実装予定）
- **コード分割**: Vue Routerの遅延読み込み
- **画像最適化**: 書影画像の遅延読み込み
- **状態管理**: Piniaでの効率的なデータ管理
- **API呼び出し最適化**: キャッシュ戦略

### 6.2 バックエンド最適化（実装済み）✅
- **ページネーション**: 20件/ページで大量データ対応
- **検索最適化**: データベースインデックス使用
- **APIレスポンス**: Laravel Resourcesで最適化

## 7. セキュリティ設計

### 7.1 フロントエンド（Phase 3実装予定）
- **XSS防止**: Vue.js自動エスケープ
- **CSRF保護**: Laravel Sanctum統合予定
- **入力検証**: フロントエンド・バックエンド二重チェック

### 7.2 バックエンド（実装済み）✅
- **APIバリデーション**: Form Request使用
- **SQLインジェクション防止**: Eloquent ORM使用
- **適切なHTTPステータスコード**: 実装済み

## 8. Phase 3実装計画

### 8.1 Step 1: 基盤構築（1時間）
1. **npm環境設定**: Vue.js 3, Vite, Tailwind CSS
2. **アプリケーション初期化**: app.js, App.vue
3. **Vue Router設定**: 基本ルート定義
4. **Pinia設定**: ストア初期化

### 8.2 Step 2: コンポーネント実装（2時間）
1. **レイアウト**: Header, Sidebar, Footer
2. **UIコンポーネント**: LoadingSpinner, SearchBox
3. **書籍コンポーネント**: BookCard, BookList

### 8.3 Step 3: ページ実装（2時間）
1. **書籍一覧ページ**: API統合、検索・フィルター
2. **書籍登録ページ**: フォーム、バリデーション
3. **書籍編集・詳細ページ**: CRUD操作完成

### 8.4 Step 4: 統合・調整（30分）
1. **API統合テスト**: 全エンドポイント動作確認
2. **レスポンシブ調整**: モバイル・デスクトップ対応
3. **エラーハンドリング**: 通知システム実装

## 9. 品質保証

### 9.1 テスト戦略（Phase 4実装予定）
- **Unit Testing**: Vitest使用
- **Component Testing**: Vue Test Utils
- **E2E Testing**: Playwright使用予定
- **API Testing**: 手動テスト継続

### 9.2 開発ツール設定
- **ESLint**: コード品質チェック
- **Prettier**: コードフォーマット
- **Vue DevTools**: デバッグツール

## 10. Phase 3成功基準

### 10.1 機能要件
- [ ] 書籍一覧表示（検索・フィルター・ページネーション）
- [ ] 書籍登録・編集・削除のUI実装
- [ ] レスポンシブデザイン対応
- [ ] API統合による完全なCRUD操作

### 10.2 品質要件
- [ ] 直感的なユーザーインターフェース
- [ ] 3秒以内のページ読み込み
- [ ] モバイル・デスクトップ両対応
- [ ] エラーハンドリングとユーザビリティ

**Phase 3実装開始準備完了 - Vue.js SPAの完全実装を開始します！**