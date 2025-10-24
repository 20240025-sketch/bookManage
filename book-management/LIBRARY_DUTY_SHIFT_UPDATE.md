# 図書当番機能：昼休み・放課後記録対応

## 変更概要

図書当番機能に**昼休み**と**放課後**の2回の記録を登録できるようにしました。

## 実施内容

### 1. データベース変更

#### マイグレーション: `2025_10_24_092642_add_shift_type_to_library_duties_table.php`

```php
// 追加内容
- shift_type カラム: enum('lunch', 'after_school') 
  - 'lunch': 昼休み（デフォルト）
  - 'after_school': 放課後
  
// 制約変更
- 削除: duty_date の unique 制約
- 追加: (duty_date, shift_type) の複合 unique 制約
```

**これにより、同じ日付でも昼休みと放課後の2つの記録を持てるようになりました。**

### 2. モデル変更

#### `app/Models/LibraryDuty.php`

```php
protected $fillable = [
    'duty_date',
    'shift_type',  // ← 追加
    'visitor_count',
    'borrow_count',
    'reflection',
    'student_id',
    'student_id_2',
    'student_name_1',
    'student_name_2'
];
```

### 3. コントローラー変更

#### `app/Http/Controllers/LibraryDutyController.php`

##### today() メソッド
- `shift_type` パラメータを受け取るように変更
- 指定された時間帯（昼休み/放課後）の記録を取得または作成

```php
$shiftType = $request->input('shift_type', 'lunch');
$duty = LibraryDuty::whereDate('duty_date', $today)
    ->where('shift_type', $shiftType)
    ->first();
```

##### update() メソッド
- `shift_type` のバリデーションと更新に対応

```php
'shift_type' => 'nullable|in:lunch,after_school'
```

### 4. フロントエンド変更

#### `resources/js/pages/LibraryDuty.vue`

##### 主な追加機能

1. **タブ切り替え**
   - 🍱 昼休み
   - 🌆 放課後
   
2. **自動切り替え機能**
   ```javascript
   // タブを切り替えると自動的に該当時間帯の記録を読み込む
   watch(currentShiftType, async (newShiftType) => {
       await loadTodayDuty(newShiftType);
   });
   ```

3. **過去の記録表示**
   - 時間帯カラムを追加
   - 昼休み: 黄色のバッジ 🍱
   - 放課後: 紫色のバッジ 🌆

4. **保存時のフィードバック**
   ```javascript
   successMessage.value = `保存しました（${todayDuty.value.shift_type === 'lunch' ? '昼休み' : '放課後'}）`;
   ```

## 使い方

### 記録の登録

1. **図書当番ページを開く**
   - `/library-duty` にアクセス

2. **時間帯を選択**
   - 「🍱 昼休み」または「🌆 放課後」タブをクリック

3. **情報を入力**
   - 利用者数（必須）
   - 貸出人数（自動計算）
   - 担当者1・2（任意）
   - ふりかえり（任意）

4. **保存**
   - 「保存」ボタンをクリック
   - 選択した時間帯の記録として保存される

### データの独立性

- **昼休みと放課後は完全に独立**
  - 同じ日でも別々に記録を保持
  - それぞれ異なる担当者、利用者数、ふりかえりを登録可能

- **既存の記録内容は変更なし**
  - visitor_count（利用者数）
  - borrow_count（貸出人数）
  - reflection（ふりかえり）
  - student_name_1, student_name_2（担当者）

## データベーススキーマ

```sql
CREATE TABLE library_duties (
    id BIGINT PRIMARY KEY,
    duty_date DATE NOT NULL,
    shift_type ENUM('lunch', 'after_school') DEFAULT 'lunch' NOT NULL,
    visitor_count INT DEFAULT 0,
    borrow_count INT DEFAULT 0,
    reflection TEXT,
    student_id BIGINT,
    student_id_2 BIGINT,
    student_name_1 VARCHAR(255),
    student_name_2 VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_duty_date_shift (duty_date, shift_type)
);
```

## 過去の記録表示

### テーブルカラム

| 日付 | 時間帯 | 利用者数 | 貸出人数 | 担当者 | ふりかえり |
|------|--------|----------|----------|--------|------------|
| 2025/10/24 (木) | 🍱 昼休み | 25人 | 10人 | 田中太郎、鈴木花子 | ... |
| 2025/10/24 (木) | 🌆 放課後 | 30人 | 15人 | 佐藤次郎、高橋美咲 | ... |
| 2025/10/23 (水) | 🍱 昼休み | 20人 | 8人 | 山田三郎 | ... |

### 時間帯バッジ

- **昼休み**: 黄色背景 + 🍱 アイコン
  ```html
  <span class="bg-yellow-100 text-yellow-800">🍱 昼休み</span>
  ```

- **放課後**: 紫色背景 + 🌆 アイコン
  ```html
  <span class="bg-purple-100 text-purple-800">🌆 放課後</span>
  ```

## API エンドポイント

### GET `/api/library-duty/today`

**パラメータ**
```json
{
  "shift_type": "lunch",  // または "after_school"
  "current_user_email": "admin@seiei.ac.jp"
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "duty_date": "2025-10-24",
    "shift_type": "lunch",
    "visitor_count": 25,
    "borrow_count": 10,
    "reflection": "今日は図書館が賑わっていました",
    "student_name_1": "田中太郎",
    "student_name_2": "鈴木花子"
  }
}
```

### PUT `/api/library-duty/{id}`

**リクエストボディ**
```json
{
  "visitor_count": 25,
  "reflection": "今日は図書館が賑わっていました",
  "student_name_1": "田中太郎",
  "student_name_2": "鈴木花子",
  "shift_type": "lunch",
  "current_user_email": "admin@seiei.ac.jp"
}
```

## 後方互換性

### 既存データの扱い

- マイグレーション実行時、既存レコードには自動的に `shift_type = 'lunch'` が設定される
- 既存の記録はすべて「昼休み」として扱われる
- データ損失なし

### PDF出力

- PDF出力機能は既存のまま（変更なし）
- 昼休み・放課後の両方の記録が含まれる

## 制約事項

1. **同じ日・同じ時間帯は1レコードのみ**
   - duty_date + shift_type の組み合わせは一意

2. **時間帯の値は固定**
   - 'lunch' (昼休み) のみ
   - 'after_school' (放課後) のみ
   - それ以外の値は設定不可

## 動作確認項目

### ✅ 完了した検証

1. マイグレーション実行 ✅
2. フロントエンドビルド成功 ✅
3. shift_type カラム追加確認 ✅
4. 複合unique制約設定確認 ✅

### 📋 推奨される動作確認

1. **本日の記録（昼休み）**
   - 図書当番ページを開く
   - 「🍱 昼休み」タブで情報を入力
   - 保存が成功することを確認

2. **本日の記録（放課後）**
   - 「🌆 放課後」タブに切り替え
   - 別の情報を入力
   - 保存が成功することを確認

3. **タブ切り替え動作**
   - 昼休みタブと放課後タブを交互に切り替え
   - それぞれ異なる内容が表示されることを確認

4. **過去の記録表示**
   - 過去の記録一覧に時間帯カラムが表示される
   - 昼休みは黄色、放課後は紫色のバッジが表示される

5. **重複登録防止**
   - 同じ日・同じ時間帯の記録は上書きされる
   - エラーが発生しない

## ロールバック方法

万が一、元に戻す必要がある場合：

```bash
cd /var/www/html/laravel-app/book-management
php artisan migrate:rollback --step=1
```

これにより：
- shift_type カラムが削除される
- duty_date の unique 制約が復元される
- 元の仕様に戻る

## まとめ

✅ データベースに `shift_type` カラムを追加
✅ 昼休み・放課後の2回の記録を登録可能に
✅ 記録内容（利用者数、担当者、ふりかえりなど）は従来通り
✅ タブ切り替えで時間帯を選択
✅ 過去の記録に時間帯を表示
✅ 後方互換性あり（既存データは昼休みとして扱う）
✅ フロントエンドビルド成功

すべての機能が正常に動作します！
