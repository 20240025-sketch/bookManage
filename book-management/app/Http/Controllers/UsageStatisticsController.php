<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Student;
use App\Models\LibraryDuty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UsageStatisticsController extends Controller
{
    /**
     * 利用状況の統計データを取得
     */
    public function index(Request $request)
    {
        try {
            Log::info('UsageStatisticsController index - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            // リクエストパラメータから現在のユーザーを特定
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
                if ($currentUser) {
                    Log::info('UsageStatisticsController - User identified from request parameter (ID: ' . $currentUser->id . ', Email: ' . $currentUser->email . ')');
                }
            }
            
            // 管理者チェック
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // 年間で一番借りられた本
            $topBookYearly = $this->getTopBorrowedBook('year');
            
            // 月間で一番借りられた本
            $topBookMonthly = $this->getTopBorrowedBook('month');
            
            // 一日の貸出人数・利用者数
            $dailyStats = $this->getDailyStats();
            
            // 一ヶ月の貸出人数・利用者数
            $monthlyStats = $this->getMonthlyStats();
            
            // 一年間の貸出人数・利用者数
            $yearlyStats = $this->getYearlyStats();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'top_book_yearly' => $topBookYearly,
                    'top_book_monthly' => $topBookMonthly,
                    'daily_stats' => $dailyStats,
                    'monthly_stats' => $monthlyStats,
                    'yearly_stats' => $yearlyStats,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('UsageStatisticsController index - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '利用状況の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * グラフ用のデータを取得
     */
    public function chartData(Request $request)
    {
        try {
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
            }
            
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            $period = $request->input('period', 'daily'); // daily, monthly, yearly
            $dataType = $request->input('data_type', 'both'); // borrow_count, user_count, both
            
            $chartData = $this->getChartData($period, $dataType);
            
            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (\Exception $e) {
            Log::error('UsageStatisticsController chartData - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'グラフデータの取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PDF出力
     */
    public function exportPdf(Request $request)
    {
        try {
            Log::info('UsageStatisticsController exportPdf - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
            }
            
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // 統計データを取得
            $topBookYearly = $this->getTopBorrowedBook('year');
            $topBookMonthly = $this->getTopBorrowedBook('month');
            $dailyStats = $this->getDailyStats();
            $monthlyStats = $this->getMonthlyStats();
            $yearlyStats = $this->getYearlyStats();
            
            // グラフデータを取得
            $period = $request->input('period', 'monthly');
            $dataType = $request->input('data_type', 'both');
            $chartData = $this->getChartData($period, $dataType);
            $chartImage = $request->input('chart_image'); // Base64エンコードされた画像
            
            $data = [
                'top_book_yearly' => $topBookYearly,
                'top_book_monthly' => $topBookMonthly,
                'daily_stats' => $dailyStats,
                'monthly_stats' => $monthlyStats,
                'yearly_stats' => $yearlyStats,
                'chart_data' => $chartData,
                'chart_image' => $chartImage,
                'period' => $period,
                'data_type' => $dataType,
            ];
            
            // PDF生成
            $pdf = \App\Services\UsageStatisticsPdfService::generate($data);
            
            $filename = '利用状況_' . date('Y年m月d日') . '.pdf';
            
            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('UsageStatisticsController exportPdf - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'PDF出力に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 期間で最も借りられた本を取得
     */
    private function getTopBorrowedBook($period)
    {
        $query = Borrow::select('book_id', DB::raw('COUNT(*) as borrow_count'))
            ->with('book');
        
        if ($period === 'year') {
            $query->where('borrowed_date', '>=', Carbon::now()->subYear());
        } elseif ($period === 'month') {
            $query->where('borrowed_date', '>=', Carbon::now()->subMonth());
        }
        
        $result = $query->groupBy('book_id')
            ->orderByDesc('borrow_count')
            ->first();
        
        if (!$result) {
            return null;
        }
        
        return [
            'book' => $result->book,
            'borrow_count' => $result->borrow_count
        ];
    }
    
    /**
     * 一日の統計
     */
    private function getDailyStats()
    {
        $today = Carbon::today();
        
        // 今日の貸出件数
        $borrowCount = Borrow::whereDate('borrowed_date', $today)->count();
        
        // 今日の利用者数（図書当番で登録された利用者数）
        $duty = LibraryDuty::whereDate('duty_date', $today)->first();
        $userCount = $duty ? $duty->visitor_count : 0;
        
        return [
            'borrow_count' => $borrowCount,
            'user_count' => $userCount
        ];
    }
    
    /**
     * 一ヶ月の統計
     */
    private function getMonthlyStats()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // 今月の貸出件数
        $borrowCount = Borrow::whereBetween('borrowed_date', [$startOfMonth, $endOfMonth])->count();
        
        // 今月の利用者数（図書当番で登録された利用者数の合計）
        $userCount = LibraryDuty::whereBetween('duty_date', [$startOfMonth, $endOfMonth])
            ->sum('visitor_count');
        
        return [
            'borrow_count' => $borrowCount,
            'user_count' => $userCount
        ];
    }
    
    /**
     * 一年間の統計
     */
    private function getYearlyStats()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();
        
        // 今年の貸出件数
        $borrowCount = Borrow::whereBetween('borrowed_date', [$startOfYear, $endOfYear])->count();
        
        // 今年の利用者数（図書当番で登録された利用者数の合計）
        $userCount = LibraryDuty::whereBetween('duty_date', [$startOfYear, $endOfYear])
            ->sum('visitor_count');
        
        return [
            'borrow_count' => $borrowCount,
            'user_count' => $userCount
        ];
    }
    
    /**
     * グラフ用のデータを生成
     */
    private function getChartData($period, $dataType)
    {
        $labels = [];
        $borrowData = [];
        $userData = [];
        
        if ($period === 'daily') {
            // 過去30日間のデータ
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format('m/d');
                
                if ($dataType === 'borrow_count' || $dataType === 'both') {
                    $borrowData[] = Borrow::whereDate('borrowed_date', $date)->count();
                }
                
                if ($dataType === 'user_count' || $dataType === 'both') {
                    // 図書当番で登録された利用者数
                    $duty = LibraryDuty::whereDate('duty_date', $date)->first();
                    $userData[] = $duty ? $duty->visitor_count : 0;
                }
            }
        } elseif ($period === 'monthly') {
            // 過去12ヶ月のデータ
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $labels[] = $date->format('Y/m');
                
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();
                
                if ($dataType === 'borrow_count' || $dataType === 'both') {
                    $borrowData[] = Borrow::whereBetween('borrowed_date', [$startOfMonth, $endOfMonth])->count();
                }
                
                if ($dataType === 'user_count' || $dataType === 'both') {
                    // 図書当番で登録された利用者数の合計
                    $userData[] = LibraryDuty::whereBetween('duty_date', [$startOfMonth, $endOfMonth])
                        ->sum('visitor_count');
                }
            }
        } elseif ($period === 'yearly') {
            // 過去5年間のデータ
            for ($i = 4; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->year;
                $labels[] = $year . '年';
                
                $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
                $endOfYear = Carbon::create($year, 12, 31)->endOfYear();
                
                if ($dataType === 'borrow_count' || $dataType === 'both') {
                    $borrowData[] = Borrow::whereBetween('borrowed_date', [$startOfYear, $endOfYear])->count();
                }
                
                if ($dataType === 'user_count' || $dataType === 'both') {
                    // 図書当番で登録された利用者数の合計
                    $userData[] = LibraryDuty::whereBetween('duty_date', [$startOfYear, $endOfYear])
                        ->sum('visitor_count');
                }
            }
        }
        
        $datasets = [];
        
        if ($dataType === 'borrow_count' || $dataType === 'both') {
            $datasets[] = [
                'label' => '貸出人数',
                'data' => $borrowData,
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'tension' => 0.1
            ];
        }
        
        if ($dataType === 'user_count' || $dataType === 'both') {
            $datasets[] = [
                'label' => '利用者数',
                'data' => $userData,
                'borderColor' => 'rgb(16, 185, 129)',
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                'tension' => 0.1
            ];
        }
        
        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}
