# 蔵書管理システム 設計書（Phase 2完了版）

## 1. システム設計概要
### 1.1 アーキテクチャ
- **アプリケーション構成**: SPA（Single Page Application）
- **通信方式**: RESTful API（Laravel API + Vue.js SPA）
- **データフロー**: Vue.js → Laravel API → SQLite

### 1.2 技術スタック詳細（実装確定版）
- **フロントエンド**: Vue.js 3 + Composition API + Vue Router + Pinia（状態管理）
- **バックエンド**: Laravel 12 + Eloquent ORM + API Resources ✅ 実装完了
- **スタイリング**: Tailwind CSS + Heroicons（アイコン）
- **ビルドツール**: Vite
- **データベース**: SQLite ✅ 実装完了

### 1.3 Laravel 12対応による設計変更
- **ルーティング**: RouteServiceProvider廃止 → bootstrap/app.php直接設定
- **設定ファイル**: 新しいLaravel構造に対応
- **API有効化**: withRouting()メソッド使用

## 2. プロジェクト構造設計（実装済み）
```
book-management/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── BookController.php ✅ 実装完了
│   │   ├── Requests/
│   │   │   ├── StoreBookRequest.php ✅ 実装完了
│   │   │   └── UpdateBookRequest.php ✅ 実装完了
│   │   └── Resources/
│   │       └── BookResource.php ✅ 実装完了
│   ├── Models/
│   │   └── Book.php ✅ 実装完了
│   └── Enums/
│       └── ReadingStatus.php ✅ 実装完了
├── database/
│   ├── seeders/
│   │   └── BookSeeder.php ✅ 実装完了
│   └── database.sqlite ✅ 作成完了
├── resources/
│   ├── js/（Phase 3で実装予定）
│   │   ├── app.js
│   │   ├── router/
│   │   │   └── index.js
│   │   ├── stores/
│   │   │   └── bookStore.js
│   │   ├── components/
│   │   │   ├── layouts/
│   │   │   │   ├── AppHeader.vue
│   │   │   │   ├── AppSidebar.vue
│   │   │   │   └── AppFooter.vue
│   │   │   ├── ui/
│   │   │   │   ├── LoadingSpinner.vue
│   │   │   │   ├── ConfirmDialog.vue
│   │   │   │   └── SearchBox.vue
│   │   │   └── books/
│   │   │       ├── BookCard.vue
│   │   │       ├── BookList.vue
│   │   │       ├── BookForm.vue
│   │   │       └── BookDetail.vue
│   │   ├── pages/
│   │   │   ├── BookIndex.vue
│   │   │   ├── BookCreate.vue
│   │   │   ├── BookEdit.vue
│   │   │   └── BookShow.vue
│   │   └── composables/
│   │       ├── useBooks.js
│   │       └── useFilters.js
│   ├── css/
│   │   └── app.css
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── api.php ✅ 実装完了
│   └── web.php ✅ 実装完了
├── bootstrap/
│   └── app.php ✅ APIルート有効化済み
├── config/
│   ├── database.php
│   └── cors.php
├── package.json
├── tailwind.config.js
└── vite.config.js
```

## 3. データベース設計（実装完了）
### 3.1 テーブル設計
#### books テーブル ✅ 実装完了
```sql
CREATE TABLE books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(255) NULL,
    publication_year INTEGER NULL,
    isbn VARCHAR(20) NULL,
    category VARCHAR(100) NULL,
    reading_status VARCHAR(20) NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.2 ReadingStatus Enum（実装完了）
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

    public static function options(): array
    {
        return [
            self::UNREAD->value => self::UNREAD->label(),
            self::READING->value => self::READING->label(),
            self::READ->value => self::read->label(),
        ];
    }
}
```

### 3.3 テストデータ（実装完了）
BookSeederにより以下のテストデータを投入済み：
- Laravel実践入門（技術書、既読）
- Vue.js 3 完全ガイド（技術書、読書中）
- 1984年（小説、既読）

## 4. API設計（実装完了）
### 4.1 エンドポイント一覧 ✅ 実装・動作確認済み
| HTTP Method | URI | Description | 実装状況 | テスト状況 |
|-------------|-----|-------------|----------|------------|
| GET | /api/books | 書籍一覧取得 | ✅ 完了 | ✅ 動作確認済み |
| POST | /api/books | 書籍登録 | ✅ 完了 | ⏳ Phase 3でテスト |
| GET | /api/books/{id} | 書籍詳細取得 | ✅ 完了 | ⏳ Phase 3でテスト |
| PUT | /api/books/{id} | 書籍更新 | ✅ 完了 | ⏳ Phase 3でテスト |
| DELETE | /api/books/{id} | 書籍削除 | ✅ 完了 | ⏳ Phase 3でテスト |

### 4.2 検索・フィルター機能（実装完了）
```
GET /api/books?search={keyword}&category={category}&reading_status={status}&page={page}
```

実装済み機能：
- フリーワード検索（title, author対象）
- カテゴリフィルター
- 読書状況フィルター
- ソート機能（作成日時、タイトル、著者）
- ページネーション（20件/ページ）

### 4.3 レスポンス形式（実装確認済み）
```json
{
    "data": [
        {
            "id": 1,
            "title": "Laravel実践入門",
            "author": "山田太郎",
            "publisher": "技術書出版",
            "publication_year": 2023,
            "isbn": "978-4-123456-78-9",
            "category": "技術書",
            "reading_status": "read",
            "reading_status_label": "既読",
            "created_at": "2025-07-23 10:00:00",
            "updated_at": "2025-07-23 10:00:00"
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/books?page=1",
        "last": "http://localhost:8000/api/books?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 20,
        "to": 3,
        "total": 3
    }
}
```

## 5. Laravel 12対応による重要な変更点

### 5.1 ルーティング設定（変更）
**旧方式**: `app/Providers/RouteServiceProvider.php`
**新方式**: `bootstrap/app.php`直接設定
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

### 5.2 設定ファイル構造の変更
- プロバイダーの自動登録
- 設定ファイルの簡素化
- ミドルウェアの新しい登録方式

## 6. バリデーション設計（実装完了）
### 6.1 バックエンド（Laravel）
```php
// StoreBookRequest.php & UpdateBookRequest.php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'publisher' => 'nullable|string|max:255',
        'publication_year' => 'nullable|integer|min:1000|max:2100',
        'isbn' => 'nullable|string|max:20',
        'category' => 'nullable|string|max:100',
        'reading_status' => ['required', Rule::enum(ReadingStatus::class)],
    ];
}

public function messages(): array
{
    return [
        'title.required' => 'タイトルは必須です。',
        'author.required' => '著者は必須です。',
        // ... 日本語エラーメッセージ
    ];
}
```

## 7. BookModelの設計（実装完了）
### 7.1 実装済み機能
```php
class Book extends Model
{
    protected $fillable = [
        'title', 'author', 'publisher', 'publication_year',
        'isbn', 'category', 'reading_status',
    ];

    protected $casts = [
        'reading_status' => ReadingStatus::class,
        'publication_year' => 'integer',
    ];

    // 検索スコープ
    public function scopeSearch($query, $search)
    public function scopeCategory($query, $category)
    public function scopeReadingStatus($query, $status)
}
```

## 8. Phase 3実装予定項目

### 8.1 フロントエンド環境構築
- Vue.js 3 + Composition API設定
- Vite + Tailwind CSS設定
- Vue Router 4設定
- Pinia状態管理設定

### 8.2 コンポーネント設計
- レイアウトコンポーネント（Header, Sidebar, Footer）
- UIコンポーネント（SearchBox, LoadingSpinner, ConfirmDialog）
- 書籍コンポーネント（BookCard, BookList, BookForm, BookDetail）
- ページコンポーネント（Index, Create, Edit, Show）

### 8.3 API統合
- axios設定
- API通信用composables作成
- エラーハンドリング実装
- ローディング状態管理

## 9. 開発環境情報（確定）
- **Laravel**: 12.x
- **PHP**: 8.1以上
- **SQLite**: ファイルベース
- **Node.js**: 最新LTS
- **npm/composer**: 最新版

## 10. 次フェーズでの注意点
1. **Laravel 12対応**: 新しいルーティング方式の理解
2. **API通信**: 実装済みエンドポイントとの正確な統合
3. **状態管理**: Piniaでの効率的なデータ管理
4. **レスポンシブ**: Tailwind CSSでのモバイルファースト設計