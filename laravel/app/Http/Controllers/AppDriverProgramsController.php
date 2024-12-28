<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Affiriate;
use App\External\AppDriver;
use App\Program;

/**
 * AppDriverプログラム管理コントローラー.
 */
class AppDriverProgramsController extends Controller
{
    /**
     * プログラム検索.
     * @param Request $request {@link Request}
     */
    public function index(Request $request)
    {
        $sort = $request->input('sort', 0);
        $app_driver_response = AppDriver::search();
        if ($sort != 0) {
            $sort_key = abs($sort);
            $sort_data = [];
            foreach ($app_driver_response->campaign as $campaign) {
                switch ($sort_key) {
                    case 3:
                        $sort_data[] = $campaign->subscription_duration;
                        break;
                    case 2:
                        $sort_data[] = $campaign->price;
                        break;
                    default:
                        $sort_data[] = $campaign->platform;
                        break;
                }
            }
            array_multisort($sort_data, $sort > 0 ? SORT_ASC : SORT_DESC, SORT_NUMERIC, $app_driver_response->campaign);
        }
        return view('app_driver_programs.index', ['app_driver_response' => $app_driver_response, 'sort' => $sort,]);
    }

    /**
     * AppDriverプログラム情報参照.
     * @param Request $request {@link Request}
     * @param int $app_driver_program_id AppDriverプログラムID
     */
    public function show(Request $request, int $app_driver_program_id)
    {
        $campaign = $request->all();
        $campaign['id'] = $app_driver_program_id;
        $campaign['location'] = $campaign['location'].'@@@COLLEEE_USERID@@@';

        $affiriate = Affiriate::where('asp_id', '=', 26)
            ->where('asp_affiriate_id', '=', $app_driver_program_id)
            ->first();

        return view('app_driver_programs.show', ['campaign' => $campaign, 'affiriate' => $affiriate, ]);
    }

    /**
     * プログラム情報作成.
     * @param Request $request {@link Request}
     */
    public function create(Request $request)
    {
        $device_map = ['1' => '1', '2' => '2', '3' => '3',];
        $device_id = $device_map[$request->input('platform')];

        $affiriate = Affiriate::where('asp_id', '=', 26)
            ->where('asp_affiriate_id', '=', $request->input('id'))
            ->first();

        $with_input = [
            'title' => $request->input('name'),
            'device' => [$device_id => $device_id],
            'start_at' => Carbon::parse($request->input('start_time'))->format('Y-m-d H:i'),
            'description' => $request->input('detail'),
            'end_at' => Carbon::parse($request->input('end_at'))->format('Y-m-d H:i'),
        ];

        if (isset($affiriate)) {
            $with_input['device'] = $with_input['device'] + $affiriate->program->device;
            return redirect(route('programs.edit', ['program' => $affiriate->program]))
                ->withInput($with_input);
        }
        $with_input = array_merge(
            $with_input,
            [
                'affiriate.asp_id' => 26,
                'affiriate.asp_affiriate_id' => $request->input('id'),
                'affiriate.ad_id' => $request->input('id'),
                'affiriate.img_url' => $request->input('icon'),
                'affiriate.url' => $request->input('location'),
                'program_schedule[0].reward_condition' => $request->input('remark'),
            ]
        );

        return redirect(route('programs.create'))
            ->withInput($with_input);
    }
}
