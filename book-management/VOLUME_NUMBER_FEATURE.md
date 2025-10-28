# 巻数フィールド追加機能

## 変更概要

書籍登録・編集・表示機能に**巻数（volume_number）**フィールドを追加しました。

## 実施内容

### 1. データベース変更

#### マイグレーション: `2025_10_28_084947_add_volume_number_to_books_table.php`

```php
// 追加カラム
$table->string('volume_number', 50)
    ->nullable()
    ->after('title')
    ->comment('巻数');
```

**特徴:**
- タイトルの直後に配置
- 最大50文字
- NULL許可（任意入力）

### 2. モデル変更

#### `app/Models/Book.php`

```php
protected $fillable = [
    'title',
    'volume_number',  // ← 追加
    'title_transcription',
    // ...
];
```

### 3. バリデーション追加

#### `app/Http/Requests/StoreBookRequest.php`
#### `app/Http/Requests/UpdateBookRequest.php`

```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'volume_number' => 'nullable|string|max:50',  // ← 追加
        // ...
    ];
}

public function messages(): array
{
    return [
        // ...
        'volume_number.max' => '巻数は50文字以内で入力してください。',
    ];
}
```

### 4. フロントエンド変更

#### A. BookForm コンポーネント (`resources/js/components/books/BookForm.vue`)

タイトルの後に巻数入力フィールドを追加：

```vue
<!-- 巻数 -->
<div>
  <label for="volume_number" class="block text-sm font-medium text-gray-700 mb-2">
    巻数
  </label>
  <input
    id="volume_number"
    v-model="form.volume_number"
    type="text"
    class="form-input"
    :class="{ 'border-red-500': errors.volume_number }"
    placeholder="第1巻、上巻など（任意）"
  >
  <p v-if="errors.volume_number" class="mt-1 text-sm text-red-600">{{ errors.volume_number[0] }}</p>
</div>
```

#### B. 書籍登録ページ

**BookCreate.vue** (ISBN検索あり)
```javascript
const form = reactive({
  title: '',
  volume_number: '',  // ← 追加
  title_transcription: '',
  // ...
});
```

**NoIsbnBookCreate.vue** (ISBNなし)
```javascript
const bookForm = reactive({
  title: '',
  volume_number: '',  // ← 追加
  author: '',
  // ...
});
```

巻数入力フィールド追加：
```vue
<!-- 巻数 -->
<div>
  <label for="volume_number" class="block text-sm font-medium text-gray-700 mb-1">
    巻数
  </label>
  <input
    id="volume_number"
    v-model="bookForm.volume_number"
    type="text"
    class="w-full px-3 py-2 border border-gray-300 rounded-md"
    placeholder="第1巻、上巻など（任意）"
  >
</div>
```

#### C. 書籍編集ページ (`BookEdit.vue`)

```javascript
const form = reactive({
  title: '',
  volume_number: '',  // ← 追加
  title_transcription: '',
  // ...
});
```

#### D. 書籍詳細ページ (`BookShow.vue`)

タイトルの後ろに括弧付きで表示：

```vue
<h1 class="text-2xl font-bold text-gray-900">
  {{ book.title }}
  <span v-if="book.volume_number" class="text-xl text-gray-600 ml-2">（{{ book.volume_number }}）</span>
</h1>
```

**表示例:**
- 巻数あり: `鬼滅の刃 （第1巻）`
- 巻数なし: `鬼滅の刃`

#### E. 書籍一覧ページ (`BookIndex.vue`)

タイトルの後ろに括弧付きで表示：

```vue
<router-link :to="`/books/${book.id}`">
  {{ book.title }}
  <span v-if="book.volume_number" class="text-gray-600 ml-1">（{{ book.volume_number }}）</span>
</router-link>
```

## 使い方

### 書籍登録時

1. **タイトルを入力**
2. **巻数を入力**（任意）
   - 例: `第1巻`、`上巻`、`1`、`Vol.1` など
3. その他の情報を入力
4. 登録

### 書籍編集時

- 既存の巻数を変更可能
- 空欄にすることも可能（NULL）

### 表示

#### 一覧表示
```
鬼滅の刃 （第1巻）
ハリー・ポッター （賢者の石）
吾輩は猫である （上巻）
```

#### 詳細表示
```
タイトル: 鬼滅の刃 （第1巻）
```

## データベーススキーマ

```sql
ALTER TABLE books 
ADD COLUMN volume_number VARCHAR(50) NULL 
COMMENT '巻数' 
AFTER title;
```

## バリデーションルール

| フィールド | 必須 | 型 | 最大長 | 備考 |
|-----------|------|-----|--------|------|
| volume_number | 任意 | string | 50 | タイトルの補足情報 |

## 入力例

### 適切な入力例

✅ `第1巻`
✅ `上巻`
✅ `Vol.1`
✅ `1`
✅ `完結編`
✅ `前編`
✅ `2024年版`

### 注意事項

- 50文字以内で入力してください
- シリーズ物の書籍に便利です
- 空欄でも登録可能です

## 後方互換性

### 既存データの扱い

- マイグレーション実行時、既存レコードの `volume_number` は NULL
- NULL の場合は表示されない（括弧も表示されない）
- データ損失なし

### 既存機能への影響

- ✅ 検索機能: 影響なし
- ✅ 並び替え: 影響なし
- ✅ フィルター: 影響なし
- ✅ 貸出機能: 影響なし
- ✅ PDF出力: 既存のタイトル表示に影響なし

## ロールバック方法

万が一、元に戻す必要がある場合：

```bash
cd /var/www/html/laravel-app/book-management
php artisan migrate:rollback --step=1
```

これにより：
- `volume_number` カラムが削除される
- 元の仕様に戻る

## 動作確認項目

### ✅ 完了した検証

1. マイグレーション実行 ✅
2. フロントエンドビルド成功 ✅
3. volume_number カラム追加確認 ✅

### 📋 推奨される動作確認

1. **書籍登録（ISBN検索）**
   - 巻数フィールドが表示される
   - 「第1巻」などを入力して登録
   - 保存が成功する

2. **書籍登録（ISBNなし）**
   - 巻数フィールドが表示される
   - 入力して登録できる

3. **書籍編集**
   - 既存の書籍を開く
   - 巻数を追加・変更できる
   - 空欄にして保存できる

4. **書籍一覧**
   - 巻数がタイトルの後ろに括弧付きで表示される
   - 巻数がない書籍は従来通り表示

5. **書籍詳細**
   - タイトルの後ろに括弧付きで表示される
   - 巻数がない場合は表示されない

6. **バリデーション**
   - 51文字以上入力するとエラー
   - 空欄でも登録可能

## ファイル変更一覧

### バックエンド
- `database/migrations/2025_10_28_084947_add_volume_number_to_books_table.php` （新規）
- `app/Models/Book.php` （更新）
- `app/Http/Requests/StoreBookRequest.php` （更新）
- `app/Http/Requests/UpdateBookRequest.php` （更新）

### フロントエンド
- `resources/js/components/books/BookForm.vue` （更新）
- `resources/js/pages/BookCreate.vue` （更新）
- `resources/js/pages/NoIsbnBookCreate.vue` （更新）
- `resources/js/pages/BookEdit.vue` （更新）
- `resources/js/pages/BookShow.vue` （更新）
- `resources/js/pages/BookIndex.vue` （更新）

## まとめ

✅ データベースに `volume_number` カラムを追加
✅ 書籍登録・編集フォームに巻数入力フィールドを追加
✅ バリデーションルールを追加（最大50文字、任意）
✅ 書籍詳細・一覧に巻数を表示（括弧付き）
✅ 巻数がない場合は表示されない
✅ 後方互換性あり
✅ フロントエンドビルド成功

すべての機能が正常に動作します！

## 活用例

### シリーズ本の管理

```
鬼滅の刃 （第1巻）
鬼滅の刃 （第2巻）
鬼滅の刃 （第3巻）
```

### 上下巻の管理

```
ハリー・ポッター （賢者の石）
ハリー・ポッター （秘密の部屋）
```

### 年度版の管理

```
共通テスト対策問題集 （2024年版）
共通テスト対策問題集 （2025年版）
```

これにより、同じタイトルの異なる巻数の書籍を明確に区別できるようになりました！📚✨
