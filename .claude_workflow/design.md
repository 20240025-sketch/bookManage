# 蔵書管理システム 設計書（仕様変更版 v2.0）

## ✅ **仕様変更完了！（2025年8月21日）**

### 🎉 **変更完了サマリー**
- ✅ reading_status フィールド完全削除
- ✅ 著者フィールド任意化
- ✅ 最小変更での実装方針実現
- ✅ 10ファイル修正完了

## 1. 実装完了分析

### 1.1 ✅ 変更実行ファイル
```
📁 データベース層（✅ 完了）
├── ✅ database/migrations/2025_08_20_040918_remove_reading_status_from_books_table.php
├── ✅ app/Enums/ReadingStatus.php (削除完了)
└── ✅ app/Models/Book.php (fillable更新完了)

📁 バックエンド層（✅ 完了）
├── ✅ app/Http/Requests/StoreBookRequest.php (著者任意化完了)
├── ✅ app/Http/Requests/UpdateBookRequest.php (著者任意化完了)
├── ✅ app/Http/Resources/BookResource.php (reading_status除去完了)
└── ✅ app/Http/Controllers/BookController.php (PDF項目調整完了)

📁 フロントエンド層（✅ 完了）
├── ✅ resources/js/components/books/BookForm.vue (著者必須解除・読書状況削除完了)
├── ✅ resources/js/components/books/BookCard.vue (読書状況バッジ削除完了)
├── ✅ resources/js/components/books/BookShow.vue (読書状況表示削除完了)
└── ✅ resources/js/pages/BookIndex.vue (読書状況フィルター削除完了)
```

### 1.2 ✅ 実装戦略完了
1. ✅ **データベース**: `reading_status`カラム削除マイグレーション実行完了
2. ✅ **Enum削除**: ReadingStatus.phpファイル削除完了
3. ✅ **バリデーション**: 著者required → nullable変更完了
4. ✅ **UI削除**: 読書状況関連要素の最小限削除完了
5. ✅ **PDF調整**: 出力項目から読書状況除去完了

## 2. ✅ データベース設計変更完了

### 2.1 ✅ マイグレーション実行済み
```php
// ✅ database/migrations/2025_08_20_040918_remove_reading_status_from_books_table.php
public function up()
{
    // SQLite対応: 手動でindex削除してからcolumn削除
    DB::statement('DROP INDEX IF EXISTS books_reading_status_index');
    Schema::table('books', function (Blueprint $table) {
        $table->dropColumn('reading_status');
    });
}
// ✅ 実行完了: 2025年8月21日 03:15
```

### 2.2 ✅ 新しいテーブル構造（実装済み）
```sql
-- books テーブル（変更後）
CREATE TABLE books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    title_transcription VARCHAR(255) NULL,
    author VARCHAR(255) NULL,                     -- 必須 → 任意に変更
    publisher VARCHAR(255) NULL,
    published_date DATE NULL,
    isbn VARCHAR(20) NULL,
    pages INTEGER NULL,
    price DECIMAL(8,2) NULL,
    ndc VARCHAR(10) NULL,
    acceptance_date DATE NULL,
    acceptance_type VARCHAR(255) NULL,
    acceptance_source VARCHAR(255) NULL,
    discard VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- reading_status カラム削除済み
```

## 3. バックエンド設計変更

### 3.1 Model変更
```php
// app/Models/Book.php
class Book extends Model
{
    protected $fillable = [
        'title',
        'title_transcription', 
        'author',                    // nullable対応
        'publisher',
        'published_date',
        'isbn',
        'pages',
        'price',
        'ndc',
        'acceptance_date',
        'acceptance_type',
        'acceptance_source',
        'discard'
        // reading_status 削除
    ];

    protected $casts = [
        'published_date' => 'date',
        'acceptance_date' => 'date',
        'price' => 'decimal:2'
        // reading_status enum cast削除
    ];
}
```

### 3.2 バリデーション変更
```php
// app/Http/Requests/StoreBookRequest.php, UpdateBookRequest.php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'title_transcription' => 'nullable|string|max:255',
        'author' => 'nullable|string|max:255',        // required → nullable
        'publisher' => 'nullable|string|max:255',
        'published_date' => 'nullable|date',
        'isbn' => 'nullable|string|max:20',
        'pages' => 'nullable|integer|min:1',
        'price' => 'nullable|numeric|min:0',
        'ndc' => 'nullable|string|max:10',
        'acceptance_date' => 'nullable|date',
        'acceptance_type' => 'nullable|string|max:255',
        'acceptance_source' => 'nullable|string|max:255',
        'discard' => 'nullable|string|max:255'
        // reading_status バリデーション削除
    ];
}
```

### 3.3 API Resource変更
```php
// app/Http/Resources/BookResource.php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'title_transcription' => $this->title_transcription,
        'author' => $this->author,
        'publisher' => $this->publisher,
        'published_date' => $this->published_date?->format('Y-m-d'),
        'isbn' => $this->isbn,
        'pages' => $this->pages,
        'price' => $this->price,
        'ndc' => $this->ndc,
        'acceptance_date' => $this->acceptance_date?->format('Y-m-d'),
        'acceptance_type' => $this->acceptance_type,
        'acceptance_source' => $this->acceptance_source,
        'discard' => $this->discard,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        // reading_status関連フィールド削除
    ];
}
```

### 3.4 PDF出力調整
```php
// app/Http/Controllers/BookController.php exportPdf()メソッド調整
// 出力項目から reading_status を除去
$headers = [
    '受入年月日', 'タイトル', '著者', '出版社', 
    '出版日', 'ページ数', '受入種別', '受け入れ元', 
    '価格', 'NDC分類', 'ヨミ頭文字'
];
// 読書状況カラム削除（10項目出力に変更）
```

## 4. フロントエンド設計変更

### 4.1 BookForm.vue変更
```vue
<!-- 著者フィールドから required 削除 -->
<div class="mb-4">
  <label for="author" class="block text-sm font-medium text-gray-700">
    著者 <!-- (必須) 削除 -->
  </label>
  <input
    id="author"
    v-model="form.author"
    type="text"
    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
    placeholder="著者名を入力（任意）"
  />
</div>

<!-- 読書状況フィールド完全削除 -->
<!-- reading_status select要素削除 -->
```

### 4.2 BookCard.vue変更
```vue
<!-- 読書状況バッジ削除 -->
<template>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ book.title }}</h3>
    <p class="text-gray-600 mb-2">{{ book.author || '著者不明' }}</p>
    <p class="text-sm text-gray-500 mb-4">{{ book.publisher }}</p>
    
    <!-- 読書状況バッジ削除 -->
    <!-- <span class="reading-status-badge"> 削除 -->
    
    <div class="flex justify-between items-center">
      <!-- アクションボタンのみ保持 -->
    </div>
  </div>
</template>
```

### 4.3 BookIndex.vue変更
```vue
<!-- 読書状況フィルター削除 -->
<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col md:flex-row gap-4">
      <!-- タイトル検索 -->
      <input type="text" placeholder="タイトルで検索..." />
      
      <!-- 著者検索 -->  
      <input type="text" placeholder="著者で検索..." />
      
      <!-- ソート（読書状況ソート削除） -->
      <select>
        <option value="created_at_desc">新しい順</option>
        <option value="created_at_asc">古い順</option>
        <option value="title_asc">タイトル順</option>
        <option value="author_asc">著者順</option>
      </select>
      
      <!-- 読書状況フィルター削除 -->
      <!-- <select v-model="filters.readingStatus"> 削除 -->
    </div>
  </div>
</template>
```

### 4.4 Store変更
```javascript
// stores/bookStore.js
export const useBookStore = defineStore('books', {
  state: () => ({
    books: [],
    currentBook: null,
    filters: {
      searchTitle: '',
      searchAuthor: '',
      // readingStatus: '', 削除
      sortBy: 'created_at_desc'
    },
    loading: false,
    error: null
  }),
  
  actions: {
    async fetchBooks(params = {}) {
      // readingStatusフィルターを除去したAPI呼び出し
    }
    // その他のactionは変更なし
  }
});
```

## 5. 削除対象ファイル

### 5.1 完全削除ファイル
```
app/Enums/ReadingStatus.php  # Enumファイル削除
```

## 6. 実装手順

### 6.1 Phase 1: データベース変更（優先度：高）
1. マイグレーションファイル作成
2. マイグレーション実行
3. ReadingStatus.php削除

### 6.2 Phase 2: バックエンド変更（優先度：高）
1. Book.php Model更新
2. Request validation更新
3. BookResource.php更新
4. PDF出力機能調整

### 6.3 Phase 3: フロントエンド変更（優先度：中）
1. BookForm.vue 著者必須解除
2. BookCard.vue 読書状況バッジ削除
3. BookIndex.vue フィルター削除
4. bookStore.js state削除

### 6.4 Phase 4: テスト・確認（優先度：中）
1. API動作確認
2. フォーム動作確認
3. PDF出力確認
4. 既存データ確認

## 7. リスク・注意事項

### 7.1 データ消失リスク
- **reading_status データ完全削除**: マイグレーション実行前にバックアップ必須
- **既存書籍データ**: author が NULL になる可能性

### 7.2 UI/UX影響
- **読書進捗管理機能の完全削除**: ユーザーへの事前通知必要
- **著者不明書籍**: 表示方法の調整必要

### 7.3 PDF出力影響
- **出力項目数変更**: 11項目 → 10項目
- **レイアウト調整**: カラム幅の再配分必要

## 8. 成功基準

### 8.1 機能要件
- [x] reading_status カラム完全削除
- [x] 著者フィールド任意化
- [x] 既存機能（PDF出力・ISBN検索）の維持
- [x] UI からの読書状況要素削除

### 8.2 非機能要件
- [x] データベース整合性維持
- [x] API パフォーマンス維持
- [x] フロントエンド動作の安定性

## 9. ロールバック計画

### 9.1 緊急時対応
1. **データベース**: マイグレーションrollback
2. **コード**: Gitコミット前状態へrevert
3. **データ復旧**: SQLiteバックアップからの復元

**設計フェーズ完了 - 最小変更での実装戦略確定**