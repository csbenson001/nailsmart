<?php

namespace App\Http\Controllers\Admin;

use App\AdminSetting;
use App\Http\Controllers\Controller;
use DB;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use LicenseBoxAPI;
use Session;
use Symfony\Component\HttpFoundation\Response;

class AdminSettingController extends Controller
{
    public function pp()
    {
        abort_if(Gate::denies('privacy_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pp = AdminSetting::get()->first();

        return view('admin.pp.index', ['pp' => $pp->pp]);
    }
    public function allSetting()
    {

        $data = AdminSetting::get()->first();
        $envData = $this->envRead();
        $emailData['country_code'] = $data['country_code'];
        $emailData['verification'] = $data['verification'];
        $emailData['APP_ID'] = $envData['APP_ID'];
        $emailData['PROJECT_NUMBER'] = $envData['PROJECT_NUMBER'];
        return response()->json(['msg' => null, 'data' => $emailData, 'success' => true], 200);
    }
    public function updatePP(Request $request)
    {
        abort_if(Gate::denies('privacy_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pp = AdminSetting::get()->first();
        $pp->pp = $request->pp;
        $pp->update();
        return back()->withStatus(__('Privacy and Policy  is updated successfully.'));
    }
    public function updateBase(Request $request)
    {

        $data = AdminSetting::get()->first();

        $data->update($request->all());
        return back()->withStatus(__('Record  is updated successfully.'));
    }
    public function ppApi()
    {

        $pp = AdminSetting::get(['pp'])->first();

        return response()->json(['msg' => null, 'data' => $pp, 'success' => true], 200);
    }
    public function index()
    {

        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $master = array();
        $master = $this->envRead();
        $data = AdminSetting::get()->first();
        $currency = DB::table('currency')->get();
        $currency = $currency->toArray();
        $master['moderation'] = $data['moderation'];
        $master['notification'] = $data['notification'];
        $master['currency_symbol'] = $data['currency_symbol'];
        $master['time_slot_length'] = $data['time_slot_length'];
        $master['admin_per'] = $data['admin_per'];
        $master['currency'] = $data['currency'];
        $master['verification'] = $data['verification'];
        $master['sms_gateway'] = $data['sms_gateway'];
        $master['country_code'] = $data['country_code'];
        $master['offline_payment'] = $data['offline_payment'];
        $master['stipe_status'] = $data['stipe_status'];
        $master['paypal_status'] = $data['paypal_status'];
        $master['razor_status'] = $data['razor_status'];
        $master['phone_no'] = $data['phone_no'];
        $master['email'] = $data['email'];
        $master['address'] = $data['address'];
        $master['android_version'] = $data['android_version'];
        $master['ios_version'] = $data['ios_version'];

        return view('admin.setting.index', compact(['master', 'currency']));
    }
    public function basicUpdate(Request $request)
    {
        $reqData = array();
        $data = AdminSetting::get()->first();
        $reqData['notification'] = $request->has('notification') ? 1 : 0;
        $reqData['moderation'] = $request->has('moderation') ? 1 : 0;
        $reqData['offline_payment'] = $request->has('offline_payment') ? 1 : 0;
        $reqData['time_slot_length'] = $request->time_slot_length;

        $currency = DB::table('currency')->where('code', $request->currency)->first();

        $reqData['currency_symbol'] = $currency->symbol;
        $reqData['currency'] = $currency->code;
        $reqData['admin_per'] = $request->admin_per;

        if ($request->main_logo && $request->main_logo != "undefined") {
            $image = $request->file('main_logo');
            $input['imagename'] = 'blue.png';
            $destinationPath = public_path('/argon/img/brand');
            $image->move($destinationPath, $input['imagename']);
        }
        $data->update($reqData);

        return back()->withStatus(__('Setting  is updated successfully.'));

        dd($request->all());
    }
    public function envRead()
    {

        $data = [

            'TWILIO_SID' => ' ',
            'TWILIO_AUTH_TOKEN' => ' ',
            'TWILIO_NUMBER' => ' ',
            'TEXT_LOCAL_API' => ' ',
            'STRIPE_SECRET' => ' ',
            'STRIPE_KEY' => ' ',
            'P_PRODUCTION_CLIENT_ID' => ' ',
            'P_SANDBOX_CLIENT_ID' => ' ',
            'RAZOR_ID' => ' ',
            'APP_ID' => ' ',
            'REST_API_KEY' => ' ',
            'USER_AUTH_KEY' => ' ',
            'PROJECT_NUMBER' => ' ',
        ];
        if (count($data) > 0) {

            if (is_writeable("../.env")) {

                $env = file_get_contents('../.env');

                $env = preg_split('/\s+/', $env);

                foreach ((array) $data as $key => $vaue) {

                    foreach ($env as $env_key => $env_value) {

                        $entry = explode("=", $env_value, 2);

                        if ($entry[0] == $key) {
                            $data[$key] = $entry[1];

                        }
                    }
                }
                return $data;
            } else {
                return $data;
            }
        }
    }

    public function updateEmail(Request $request)
    {

        $data = [
            'MAIL_HOST' => $request->MAIL_HOST,
            'MAIL_PORT' => $request->MAIL_PORT,
            'MAIL_USERNAME' => $request->MAIL_USERNAME,
            'MAIL_PASSWORD' => $request->MAIL_PASSWORD,
            'MAIL_ENCRYPTION' => $request->MAIL_ENCRYPTION,
            'MAIL_DRIVER' => $request->MAIL_DRIVER,
        ];

        $this->updateENV($data);

        return redirect('setting')->withStatus(__('Email Configuration updated successfully.'));
    }
    public function updateStripe(Request $request)
    {
        $data = [
            'STRIPE_SECRET' => $request->STRIPE_SECRET,
            'STRIPE_KEY' => $request->STRIPE_KEY,
        ];

        $this->updateENV($data);

        return redirect('setting')->withStatus(__('Stripe Configuration updated successfully.'));
    }
    public function updateNotification(Request $request)
    {
        $data = [

            'APP_ID' => $request->APP_ID,
            'REST_API_KEY' => $request->REST_API_KEY,
            'USER_AUTH_KEY' => $request->USER_AUTH_KEY,
            'PROJECT_NUMBER' => $request->PROJECT_NUMBER,
        ];

        $this->updateENV($data);

        return redirect('setting')->withStatus(__('OneSignal Configuration updated successfully.'));
    }

    public function updateENV($data)
    {
        if (is_writeable("../.env")) {

            $env = file_get_contents('../.env');

            $env = preg_split('/\s+/', $env);

            foreach ((array) $data as $key => $value) {

                foreach ($env as $env_key => $env_value) {

                    $entry = explode("=", $env_value, 2);

                    if ($entry[0] == $key) {

                        $env[$env_key] = $key . "=" . $value;
                    } else {

                        $env[$env_key] = $env_value;
                    }
                }
            }

            $env = implode("\n", $env);

            file_put_contents('../.env', $env);
            \Artisan::call('config:clear');
            \Artisan::call('cache:clear');
            return true;
        } else {

            return abort(500, 'Don`t have write permission');
        }
    }
    public function apiPaymentData()
    {
        $data = AdminSetting::get(['offline_payment', 'stipe_status', 'paypal_status', 'razor_status', 'currency', 'currency_symbol'])->first();
        $data['STRIPE_SECRET'] = env('STRIPE_SECRET');
        $data['STRIPE_KEY'] = env('STRIPE_KEY');
        $data['P_PRODUCTION_CLIENT_ID'] = env('P_PRODUCTION_CLIENT_ID');
        $data['P_SANDBOX_CLIENT_ID'] = env('P_SANDBOX_CLIENT_ID');
        $data['RAZOR_ID'] = env('RAZOR_ID');
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }
    public function contactUs()
    {
        $pp = AdminSetting::get(['email', 'address', 'phone_no'])->first();

        return response()->json(['msg' => null, 'data' => $pp, 'success' => true], 200);
    }
    public function apiNotiKey()
    {
        $data['APP_ID'] = env('APP_ID');
        $data['REST_API_KEY'] = env('REST_API_KEY');
        $data['USER_AUTH_KEY'] = env('USER_AUTH_KEY');
        $data['PROJECT_NUMBER'] = env('PROJECT_NUMBER');
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }
    public function active(Request $request)
    {

        $api = new LicenseBoxAPI();
        $result = $api->activate_license($request->license_code, $request->name);
        if ($result['status'] === true) {
            $request->session()->forget('status');
            Artisan::call('up');

            return redirect('/');
        } else {

            $url = back()->getTargetUrl() . '?status=' . $result['message'];
            return redirect($url);

            session(['status' => $result['message']]);
            return Redirect::back()->withInput();

        }
    }

    public function healthcheck()
    {
        //$d = AdminSetting::first();
        $d = "tests";
        return response()->json(['msg' => null, 'data' => $d, 'success' => true], 200);
    }

    public function setup(Request $request)
    {
        $data['DB_HOST'] = $request->db_host;
        $data['DB_DATABASE'] = $request->db_name;
        $data['DB_USERNAME'] = $request->db_user;
        $data['DB_PASSWORD'] = $request->db_pass;
        $this->updateENV($data);
        return response()->json(['data' => url('login'), 'success' => true], 200);
    }
}
