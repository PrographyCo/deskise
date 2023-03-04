<?php

namespace App\Helpers;

use Illuminate\Contracts\Support\Arrayable;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\ServiceAccount;

class FirebaseHelper
{
    private static $factory = null;
    private static $database = null;
    private static $messaging = null;
    private static $ref_prefixs = [];

    public function __construct($prefix='')
    {
        if ($prefix!=='')
            self::$ref_prefixs[] = $prefix;
    }

    private static function factory()
    {
        return self::$factory = self::$factory ?? (new Factory)
            ->withServiceAccount(ServiceAccount::fromValue(config('app.firebase.credentials')));
    }
    private static function Database() :Database
    {
        return self::$database = self::$database ?? self::factory()
            ->withDatabaseUri(config('app.firebase.database_uri'))
            ->createDatabase();
    }
    private static function Messaging() :Messaging
    {
        return self::$messaging = self::$messaging ?? self::factory()->createMessaging();
    }

    private static function getReference($ref) :Reference
    {
        return self::Database()->getReference((implode('/',self::$ref_prefixs)).$ref);
    }

    public function find($ref)
    {
        return self::getReference($ref)->getValue();
    }
    public function update($ref, Arrayable|array $data)
    {
        return self::getReference($ref)->update((array)$data);
    }
    public function create($ref, Arrayable|array $data)
    {
        return self::getReference($ref)->set((array)$data);
    }
    public function delete($ref)
    {
        return self::getReference($ref)->remove();
    }

    public static function notify(array|Arrayable $device_tokens, $title, $body, $image='',array|Arrayable $data=[]) :MulticastSendReport
    {
        return self::Messaging()
            ->sendMulticast(
                CloudMessage::new()->withNotification([
                    'title' =>  $title,
                    'body'  =>  $body,
                    'image' =>  $image,
                ])->withData((array)$data),
                $device_tokens);
    }
}
