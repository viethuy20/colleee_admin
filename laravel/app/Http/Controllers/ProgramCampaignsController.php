<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Program;
use App\ProgramCampaign;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProgramCampaignsController extends Controller
{
    /**
     * プログラム検索.
     * @param Request $request {@link Request}
     */
    public function index(Program $program)
    {
        $campaign_list = $program->campaigns()
        ->orderBy('id', 'asc')
        ->get()
        ->pluck(null, 'id');
        return view('campaigns.index',['program' => $program, 'campaign_list' => $campaign_list]);
    }


    /**
     * プログラム情報作成.
     * @param Request $request {@link Request}
     */
    public function create(Program $program)
    {
        return $this->edit($program->default_campaign);
    }

    public function edit(ProgramCampaign $program_campaign)
    {
        $program = $program_campaign->program;
        $datas = ['program' => $program, 'campaign' => $program_campaign];

        return view('campaigns.edit',$datas);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $program = Program::findOrFail($request->input('program_id'));
        $campaign_list = $program->campaigns()
                            ->orderBy('id', 'asc')
                            ->get()
                            ->pluck(null, 'id');
        if ($request->filled('id')) {
            $campaign = ProgramCampaign::findOrFail($request->input('id'));
            $campaign_list = $campaign_list->reject(function ($item) use ($data) {
                return $item->id == $data['id'];
            });
        } else {
            if (!$request->filled('program_id')) {
                abort(404, 'Not Found.');
            }
            $campaign = $program->default_campaign;
        }

        $validateRules = [
            'campaigns.url' => ['nullable','url'],
            'campaigns.start_at_date' => ['required', 'date_format:"Y-m-d"'],
            'campaigns.start_at_time' => ['required', 'date_format:"H:i"'],
            'campaigns.stop_at_date' => ['required', 'date_format:"Y-m-d"'],
            'campaigns.stop_at_time' => ['required', 'date_format:"H:i"'],
        ];
        $this->validate(
            $request,
            $validateRules,
            [],
            array_merge(
                [
                    'campaigns.url' => 'URL',
                    'campaigns.start_at_date' => '開始日',
                    'campaigns.start_at_time' => '開始時',
                    'campaigns.stop_at_date' => '終了日',
                    'campaigns.stop_at_time' => '終了時',

                ])
        );
        $validatedData['start_at'] = $data['campaigns']['start_at_date'].' '.$data['campaigns']['start_at_time'];
        $validatedData['stop_at'] = $data['campaigns']['stop_at_date'].' '.$data['campaigns']['stop_at_time'];
        $rules = [ 'start_at' => 'before:stop_at'];
        $messages = ['start_at.before' => '開始日時は終了日時以前の日付にしてください'];
        $validator = Validator::make($validatedData, $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        //check time
        $overlapping = false;
        $program_campaigns_list_check_time = $campaign_list;
        if($program_campaigns_list_check_time->count() > 0){
            foreach ($program_campaigns_list_check_time as $campaign_check) {
                $campaignStart = $campaign_check['start_at'];
                $campaignStop = $campaign_check['stop_at'];
                if ($validatedData['start_at'] < $campaignStop && $validatedData['stop_at'] > $campaignStart) {
                    $overlapping = true;
                    break;
                }
            }
            if ($overlapping) {
                $validator = Validator::make([], []);
                $validator->errors()->add('time_period', '登録済みキャンペーンと期間が重複しています');
                throw new ValidationException($validator);
            }
        }
        // 開始日時
        $start_at = $data['campaigns']['start_at_date'].' '.$data['campaigns']['start_at_time'];
        // 終了日時
        $stop_at = $data['campaigns']['stop_at_date'].' '.$data['campaigns']['stop_at_time'];
        $existingQuestions =  [];
        //case create
        $campaign_item = [
            'title'  => $data['campaigns']['title'],
            'campaign' => $data['campaigns']['campaign'],
            'url'      => $data['campaigns']['url'],
            'start_at' => $start_at,
            'stop_at' => $stop_at
        ];
        $campaign->fill($campaign_item);
        // 保存実行
        $res = $campaign->saveCampaign(Auth::user()->id);

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '情報の編集に失敗しました');
        }
        return redirect(route('program_campaigns.index',['program' => $program]));}
}
