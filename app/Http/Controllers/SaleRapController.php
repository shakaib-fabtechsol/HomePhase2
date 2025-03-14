<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use App\Models\Task;
use App\Models\Order;
use App\Models\BusinessProfile;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SaleRapController extends Controller
{
    public function Dashboard()
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            $assignPros = User::where('role', 2)->where('assign_sales_rep', Auth::id())->count();
            $newPros = User::where('role', 2)->whereDate('created_at', Carbon::today())->count();
            $recentDeal = Deal::where('publish', 1)->whereDate('created_at', Carbon::today())->count();
            $recetPublishDeals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
                ->orderBy('deals.id', 'desc')
                ->select(
                    'deals.id',
                    'deals.service_title',
                    'deals.service_category',
                    'deals.service_description',
                    'deals.pricing_model',
                    'deals.flat_rate_price',
                    'deals.hourly_rate',
                    'deals.images',
                    'deals.videos',
                    'deals.price1',
                    'deals.user_id',
                    'deals.created_at',
                    'users.name as user_name',
                    'users.personal_image'
                )
                ->groupBy(
                    'deals.id',
                    'deals.service_title',
                    'deals.service_category',
                    'deals.service_description',
                    'deals.pricing_model',
                    'deals.flat_rate_price',
                    'deals.hourly_rate',
                    'deals.price1',
                    'deals.images',
                    'deals.videos',
                    'deals.created_at',
                    'deals.user_id',
                    'users.name',
                    'users.personal_image'
                )->where('deals.publish', 1)->orderBy('deals.id', 'desc')->limit(2)->get();

            $totalRevenue = Order::sum('total_amount');

            $monthlyRevenue = Order::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_amount) as revenue')
            )
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get()
                ->keyBy('month')
                ->toArray();

            // Initialize an array with all months set to 0
            $allMonths = array_fill(1, 12, 0);

            // Update the array with actual revenue data
            foreach ($monthlyRevenue as $month => $data) {
                $allMonths[$month] = $data['revenue'];
            }

            // Format the data for the response
            $formattedMonthlyRevenue = [];
            foreach ($allMonths as $month => $revenue) {
                $formattedMonthlyRevenue[] = [
                    'month' => $month,
                    'revenue' => $revenue,
                ];
            }

            $weeklyRevenue = Order::select(
                DB::raw('DAYOFWEEK(created_at) as day'),
                DB::raw('SUM(total_amount) as revenue')
            )
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get()
                ->keyBy('day')
                ->toArray();

            // Initialize an array with all days of the week set to 0
            $allDays = array_fill(1, 7, 0);

            // Update the array with actual revenue data
            foreach ($weeklyRevenue as $day => $data) {
                $allDays[$day] = $data['revenue'];
            }

            // Format the data for the response
            $formattedWeeklyRevenue = [];
            foreach ($allDays as $day => $revenue) {
                $formattedWeeklyRevenue[] = [
                    'day' => $day,
                    'revenue' => $revenue,
                ];
            }

            $dailyRevenue = Order::select(
                DB::raw('DAY(created_at) as day'),
                DB::raw('SUM(total_amount) as revenue')
            )
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get()
                ->keyBy('day')
                ->toArray();

            // Initialize an array with all days of the month set to 0
            $daysInMonth = Carbon::now()->daysInMonth;
            $allDays = array_fill(1, $daysInMonth, 0);

            // Update the array with actual revenue data
            foreach ($dailyRevenue as $day => $data) {
                $allDays[$day] = $data['revenue'];
            }

            // Format the data for the response
            $formattedDailyRevenue = [];
            foreach ($allDays as $day => $revenue) {
                $formattedDailyRevenue[] = [
                    'day' => $day,
                    'revenue' => $revenue,
                ];
            }


            $reportData = Deal::select('deals.service_category', DB::raw('SUM(orders.total_amount) as revenue'))
                ->join('orders', 'orders.deal_id', '=', 'deals.id')
                ->groupBy('deals.service_category')
                ->get()
                ->map(function ($data) use ($totalRevenue) {
                    return [
                        'category' => $data->service_category,
                        'revenue' => $data->revenue,
                    ];
                })
                ->sortByDesc('revenue');

            $commission = $totalRevenue * 0.02;


            return response()->json([
                'assignPros' => $assignPros,
                'newPros' => $newPros,
                'recentDeal' => $recentDeal,
                'recetPublishDeals' => $recetPublishDeals,
                'totalRevenue' => $totalRevenue,
                'commission' => $commission,
                'top_catogory_revenue' => $reportData,
                'yearly_revenue' => $formattedMonthlyRevenue,
                'monthly_revenue' => $formattedDailyRevenue,
                'weekly_revenue' => $formattedWeeklyRevenue,
            ], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function RecenltyPublishDeals()
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $recetPublishDeals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
                ->orderBy('deals.id', 'desc')
                ->select(
                    'deals.id',
                    'deals.service_title',
                    'deals.service_category',
                    'deals.service_description',
                    'deals.pricing_model',
                    'deals.flat_rate_price',
                    'deals.hourly_rate',
                    'deals.images',
                    'deals.videos',
                    'deals.price1',
                    'deals.user_id',
                    'deals.created_at',
                    'users.name as user_name',
                    'users.personal_image'
                )
                ->groupBy(
                    'deals.id',
                    'deals.service_title',
                    'deals.service_category',
                    'deals.service_description',
                    'deals.pricing_model',
                    'deals.flat_rate_price',
                    'deals.hourly_rate',
                    'deals.price1',
                    'deals.images',
                    'deals.videos',
                    'deals.created_at',
                    'deals.user_id',
                    'users.name',
                    'users.personal_image'
                )->where('deals.publish', 1)->orderBy('deals.id', 'desc')->get();

            return response()->json([
                'recetPublishDeals' => $recetPublishDeals
            ], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function SaleAllProviders(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $allProviders = DB::table('users')
                ->leftJoin(DB::raw('(SELECT user_id, COUNT(id) as total_deals FROM deals GROUP BY user_id) as deals'), 'users.id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'users.id', '=', 'reviews.provider_id')
                ->select(
                    'users.id',
                    'users.personal_image',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'users.status',
                    DB::raw('COALESCE(deals.total_deals, 0) as total_deals'),
                    DB::raw('AVG(reviews.rating) as rating')
                )
                ->where('users.role', 2)
                ->groupBy('users.id', 'users.personal_image', 'users.name', 'users.email', 'users.status', 'users.phone', 'deals.total_deals', 'reviews.provider_id');

            if ($request->has('search')) {
                $search = $request->search;
                $allProviders->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $allProviders = $allProviders->paginate($request->providers ?? 8);

            $totalProviders = $allProviders->total();


            if ($allProviders) {
                return response()->json(['totalProviders' => $totalProviders, 'allProviders' => $allProviders], 200);
            } else {
                return response()->json(['message' => 'No Service Provider Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SaleAssignProviders(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            // Assiged

            $assignProviders = DB::table('users')
                ->leftJoin(DB::raw('(SELECT user_id, COUNT(id) as total_deals FROM deals GROUP BY user_id) as deals'), 'users.id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'users.id', '=', 'reviews.provider_id')
                ->select(
                    'users.id',
                    'users.personal_image',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'users.status',
                    DB::raw('COALESCE(deals.total_deals, 0) as total_deals'),
                    DB::raw('AVG(reviews.rating) as rating')
                )
                ->where('users.role', 2)->where('users.assign_sales_rep', Auth::id())
                ->groupBy('users.id', 'users.personal_image', 'users.name', 'users.email', 'users.status', 'users.phone', 'deals.total_deals', 'reviews.provider_id');

            if ($request->has('search')) {
                $search = $request->search;
                $assignProviders->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $assignProviders = $assignProviders->paginate($request->providers ?? 8);

            $totalAssignProviders = $assignProviders->total();

            if ($assignProviders) {
                return response()->json(['totalAssignProviders' => $totalAssignProviders, 'assignProviders' => $assignProviders], 200);
            } else {
                return response()->json(['message' => 'No Service Provider Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SaleProviderDetail($user_id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $user = User::find($user_id);
            $deals = Deal::where('user_id', $user_id)->get();
            $business = BusinessProfile::where('user_id', $user_id)->first();
            $averageRating = DB::table('reviews')->where('provider_id', $user_id)->avg('rating');
            $totalReview = DB::table('reviews')->where('provider_id', $user_id)->count();

            $stars = Review::select(
                DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
            )
                ->where('provider_id', $user_id)
                ->first();

            return response()->json(['message' => 'Provider Details', 'user' => $user, 'deals' => $deals, 'business' => $business, 'averageRating' => $averageRating, 'totalReview' => $totalReview, 'stars' => $stars], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateSaleProvider(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            $data = $request->all();

            $getProvider = User::find($request->id);
            if ($getProvider->role != 2) {
                return response()->json(['message' => 'Invalid User Id'], 401);
            }
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $getProvider->personal_image);
                if (!empty($getProvider->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $getProvider->update($data);

            return response()->json(['message' => 'Provider updated successfully', 'getProvider' => $getProvider], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SalesPersonal(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $user = User::find($request->id);
            if ($user) {
                $data = $request->all();
                if ($request->hasFile('personal_image')) {
                    $imagePath = public_path('uploads/' . $user->personal_image);
                    if (!empty($user->personal_image) && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $photo1 = $request->file('personal_image');
                    $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo1->move($photo_destination, $photo_name1);
                    $data['personal_image'] = $photo_name1;
                }
                $user->update($data);
                return response()->json(['message' => 'User Personal details updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SalesSecurity(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $user = User::find($request->id);
            if ($user) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json(['message' => 'Current password is incorrect'], 422);
                }
                $user->password = Hash::make($request->password);
                $user->save();
                return response()->json(['message' => 'User Password Updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddTask(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 3) {

            $data = $request->all();
            if ($request->hasFile('files')) {
                $photo1 = $request->file('files');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['files'] = $photo_name1;
            }
            $data['created_by'] = $userId;
            $task = Task::create($data);
            return response()->json(['message' => 'Task created successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function FetchAllTask()
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::all();


            return response()->json(['task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function ViewTask($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            $task = Task::find($id);

            return response()->json(['task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateTask(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::find($request->id);
            $data = $request->all();

            if ($request->hasFile('files')) {
                $imagePath = public_path('uploads/' . $task->files);
                if (!empty($task->files) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('files');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['files'] = $photo_name1;
            }

            $task->update($data);
            return response()->json(['message' => 'Task updated successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteTask($id)
    {

        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::find($id);
            $imagePath = public_path('uploads/' . $task->files);
            if (!empty($task->files) && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $task->delete();
            return response()->json(['message' => 'Task deleted successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetSettingSale($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $GetSettingSale = User::find($id);
            if ($GetSettingSale) {
                $setting = [
                    'name' => $GetSettingSale->name,
                    'email' => $GetSettingSale->email,
                    'phone' => $GetSettingSale->phone,
                    'personal_image' => $GetSettingSale->personal_image,
                ];
                return response()->json(['setting' => $setting], 200);
            }
            return response()->json(['message' => 'Setting not available'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SaleCustomers(Request $request)
    {
        $role = Auth::user()->role;

        if ($role == 3) {
            $customers = User::where('role', 1);
            if ($request->has('search')) {
                $search = $request->search;
                $customers->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $customers = $customers->paginate($request->clients ?? 8);

            $total_customers = $customers->total();
            if ($customers) {
                return response()->json(['total_customers' => $total_customers, 'Customers' => $customers], 200);
            } else {
                return response()->json(['message' => 'No Customer Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SaleCustomer($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $customer = User::find($id);
            if ($customer) {
                return response()->json(['Customer' => $customer], 200);
            } else {
                return response()->json(['message' => 'No Customer Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateSaleCustomer(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $data = $request->all();

            $GetSaleRep = User::find($request->id);
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
                if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $GetSaleRep->update($data);

            return response()->json(['message' => 'Customer updated successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetServiceRevenue()
    {


        $totalRevenue = Order::sum('total_amount');


        $reportData = Deal::select('deals.service_category', DB::raw('SUM(orders.total_amount) as revenue'))
            ->join('orders', 'orders.deal_id', '=', 'deals.id')
            ->groupBy('deals.service_category')
            ->get()
            ->map(function ($data) use ($totalRevenue) {
                return [
                    'category' => $data->service_category,
                    'revenue' => $data->revenue,
                    'percentage' => $totalRevenue ? round(($data->revenue / $totalRevenue) * 100, 2) : 0
                ];
            });


        return response()->json(['reportData' => $reportData], 200);
    }

    public function quarterlyReport()
    {
        $quarters = [
            'Q1' => [1, 3],  // January - March
            'Q2' => [4, 6],  // April - June
            'Q3' => [7, 9],  // July - September
            'Q4' => [10, 12] // October - December
        ];

        $quarterlyData = [];
        $previousRevenue = null;

        foreach ($quarters as $quarter => $months) {
            $revenue = Order::whereMonth('created_at', '>=', $months[0])
                ->whereMonth('created_at', '<=', $months[1])
                ->sum('total_amount');


            $growth = ($previousRevenue !== null && $previousRevenue > 0)
                ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 2) . '%'
                : '-';


            $previousRevenue = $revenue;


            $quarterlyData[] = [
                'quarter' => $quarter,
                'revenue' => $revenue,
                'growth' => $growth
            ];
        }


        return response()->json(['quarterlyData' => $quarterlyData], 200);
    }
}
