<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            // // GENERAL
            // ['setting_key'=>'site_name',       'setting_value'=>'EgoMall', 'setting_type'=>'string','setting_group'=>'general','setting_label'=>'Tên website','description'=>'Tên hiển thị của cửa hàng','created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'site_logo',       'setting_value'=>null,      'setting_type'=>'image', 'setting_group'=>'general','setting_label'=>'Logo','description'=>'URL logo (Cloudinary)','created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'site_address',    'setting_value'=>null,      'setting_type'=>'text',  'setting_group'=>'general','setting_label'=>'Địa chỉ','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'hotline',         'setting_value'=>null,      'setting_type'=>'string','setting_group'=>'general','setting_label'=>'Hotline','description'=>null,'created_at'=>$now,'updated_at'=>$now],

            // // EMAIL (password để null – sẽ mã hoá khi update)
            // ['setting_key'=>'email_from_name',    'setting_value'=>'EgoMall',         'setting_type'=>'string','setting_group'=>'email','setting_label'=>'From name','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_from_address', 'setting_value'=>null,              'setting_type'=>'email', 'setting_group'=>'email','setting_label'=>'From address','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_driver',       'setting_value'=>'smtp',            'setting_type'=>'string','setting_group'=>'email','setting_label'=>'Driver','description'=>'smtp/mailgun/sendmail...','created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_host',         'setting_value'=>'smtp.gmail.com',  'setting_type'=>'string','setting_group'=>'email','setting_label'=>'Host','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_port',         'setting_value'=>'587',             'setting_type'=>'number','setting_group'=>'email','setting_label'=>'Port','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_username',     'setting_value'=>null,              'setting_type'=>'string','setting_group'=>'email','setting_label'=>'Username','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_password',     'setting_value'=>null,              'setting_type'=>'password','setting_group'=>'email','setting_label'=>'Password','description'=>'Lưu mã hoá, không trả về khi GET','created_at'=>$now,'updated_at'=>$now],
            // ['setting_key'=>'email_encryption',   'setting_value'=>'tls',             'setting_type'=>'string','setting_group'=>'email','setting_label'=>'Encryption','description'=>'tls/ssl/null','created_at'=>$now,'updated_at'=>$now],

            // CONTACT
            ['setting_key'=>'facebook_url',    'setting_value'=>null,      'setting_type'=>'url',   'setting_group'=>'contact','setting_label'=>'Facebook','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'zalo_url',        'setting_value'=>null,      'setting_type'=>'url',   'setting_group'=>'contact','setting_label'=>'Zalo','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'youtube_url',     'setting_value'=>null,      'setting_type'=>'url',   'setting_group'=>'contact','setting_label'=>'Youtube','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'instagram_url',   'setting_value'=>null,      'setting_type'=>'url',   'setting_group'=>'contact','setting_label'=>'Instagram','description'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'tiktok_url',      'setting_value'=>null,      'setting_type'=>'url',   'setting_group'=>'contact','setting_label'=>'Tiktok','description'=>null,'created_at'=>$now,'updated_at'=>$now],
        ];

        DB::table('system_settings')->upsert(
            $rows,
            ['setting_key'], // unique
            ['setting_value','setting_type','setting_group','setting_label','description','updated_at']
        );
    }
}