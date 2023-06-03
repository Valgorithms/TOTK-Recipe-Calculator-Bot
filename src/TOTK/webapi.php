<?php

/*
 * This file is a part of the TOTK Recipe Calculator project.
 *
 * Copyright (c) 2023-present Valithor Obsidion <valzargaming@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Embed\Embed;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use \Psr\Http\Message\ServerRequestInterface;

function webapiFail($part, $id) {
    //logInfo('[webapi] Failed', ['part' => $part, 'id' => $id]);
    return new Response(($id ? 404 : 400), ['Content-Type' => 'text/plain'], ($id ? 'Invalid' : 'Missing').' '.$part);
}

function webapiSnow($string) {
    return preg_match('/^[0-9]{16,20}$/', $string);
}

$external_ip = file_get_contents('http://ipecho.net/plain');
$valzargaming_ip = gethostbyname('www.valzargaming.com');
$port = '55555';

$socket = new SocketServer(sprintf('%s:%s', '0.0.0.0', $port), [], $TOTK->loop);
$webapi = new HttpServer($loop, function (ServerRequestInterface $request) use ($TOTK, $port, $socket, $external_ip, $valzargaming_ip)
{
    /*
    $path = explode('/', $request->getUri()->getPath());
    $sub = (isset($path[1]) ? (string) $path[1] : false);
    $id = (isset($path[2]) ? (string) $path[2] : false);
    $id2 = (isset($path[3]) ? (string) $path[3] : false);
    $ip = (isset($path[4]) ? (string) $path[4] : false);
    $idarray = array(); //get from post data (NYI)
    */
    
    $echo = 'API ';
    $sub = 'index.';
    $path = explode('/', $request->getUri()->getPath());
    $repository = $sub = (isset($path[1]) ? (string) strtolower($path[1]) : false); if ($repository) $echo .= "$repository";
    $method = $id = (isset($path[2]) ? (string) strtolower($path[2]) : false); if ($method) $echo .= "/$method";
    $id2 = $repository2 = (isset($path[3]) ? (string) strtolower($path[3]) : false); if ($id2) $echo .= "/$id2";
    $ip = $partial = $method2 = (isset($path[4]) ? (string) strtolower($path[4]) : false); if ($partial) $echo .= "/$partial";
    $id3 = (isset($path[5]) ? (string) strtolower($path[5]) : false); if ($id3) $echo .= "/$id3";
    $id4 = (isset($path[6]) ? (string) strtolower($path[6]) : false); if ($id4) $echo .= "/$id4";
    $idarray = array(); //get from post data (NYI)
    //$TOTK->logger->info($echo);
    
    if ($ip) $TOTK->logger->info('API IP ' . $ip);
    $whitelist = [
        '127.0.0.1',
        $external_ip,
        $valzargaming_ip,
    ];
    $substr_whitelist = ['10.0.0.', '192.168.']; 
    $whitelisted = false;
    foreach ($substr_whitelist as $substr) if (substr($request->getServerParams()['REMOTE_ADDR'], 0, strlen($substr)) == $substr) $whitelisted = true;
    if (in_array($request->getServerParams()['REMOTE_ADDR'], $whitelist)) $whitelisted = true;
    
    if (! $whitelisted) $TOTK->logger->info('API REMOTE_ADDR ' . $request->getServerParams()['REMOTE_ADDR']);

    $webpage_content = function ($return) use ($TOTK, $port, $sub) {
        ob_start();
        include 'webapi_content.php';
        return ob_get_clean();
    };

    switch ($sub) {
        case (str_starts_with($sub, 'index.')):
            $return = '<meta http-equiv = \"refresh\" content = \"0; url = https://www.valzargaming.com/?login\" />'; //Redirect to the website to log in
            return new Response(200, ['Content-Type' => 'text/html'], $return);
            break;
        case 'github':
            $return = '<meta http-equiv = \"refresh\" content = \"0; url = ' . $TOTK->github . '\" />'; //Redirect to the website to log in
            return new Response(200, ['Content-Type' => 'text/html'], $return);
            break;
        case 'favicon.ico':
            if (! $whitelisted) {
                $TOTK->logger->info('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            $favicon = file_get_contents('favicon.ico');
            return new Response(200, ['Content-Type' => 'image/x-icon'], $favicon);
        
        case 'nohup.out':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = file_get_contents('nohup.out')) return new Response(200, ['Content-Type' => 'text/plain'], $return);
            else return new Response(501, ['Content-Type' => 'text/plain'], "Unable to access `nohup.out`");
            break;
        
        case 'botlog':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = @file_get_contents('botlog.txt') ?? '') return new Response(200, ['Content-Type' => 'text/html'], $webpage_content($return));
            else return new Response(501, ['Content-Type' => 'text/plain'], "Unable to access `botlog.txt`");
            break;
            
        case 'botlog2':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = @file_get_contents('botlog2.txt') ?? '') return new Response(200, ['Content-Type' => 'text/html'], $webpage_content($return));
            else return new Response(501, ['Content-Type' => 'text/plain'], "Unable to access `botlog2.txt`");
            break;
        
        case 'channel':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->getChannel($id)) return webapiFail('channel_id', $id);
            break;

        case 'guild':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)) return webapiFail('guild_id', $id);
            break;

        case 'bans':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->bans) return webapiFail('guild_id', $id);
            break;

        case 'channels':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->channels) return webapiFail('guild_id', $id);
            break;

        case 'members':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->members) return webapiFail('guild_id', $id);
            break;

        case 'emojis':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->emojis) return webapiFail('guild_id', $id);
            break;

        case 'invites':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->invites) return webapiFail('guild_id', $id);
            break;

        case 'roles':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->guilds->get('id', $id)->roles) return webapiFail('guild_id', $id);
            break;

        case 'guildMember':
            if (! $id || !webapiSnow($id) || !$guild = $TOTK->discord->guilds->get('id', $id)) return webapiFail('guild_id', $id);
            if (! $id2 || !webapiSnow($id2) || !$return = $guild->members->get('id', $id2)) return webapiFail('user_id', $id2);
            break;

        case 'user':
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->users->get('id', $id)) return webapiFail('user_id', $id);
            break;

        case 'userName':
            if (! $id || !$return = $TOTK->discord->users->get('name', $id)) return webapiFail('user_name', $id);
            break;
        
        case 'reset':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('git reset --hard origin/main');
            if (isset($TOTK->channel_ids['staff_bot']) && $channel = $TOTK->discord->getChannel($TOTK->channel_ids['staff_bot'])) $channel->sendMessage('Forcefully moving the HEAD back to origin/main...');
            $return = 'fixing git';
            break;
        
        case 'pull':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('git pull');
            $TOTK->logger->info('[GIT PULL]');
            if (isset($TOTK->channel_ids['staff_bot']) && $channel = $TOTK->discord->getChannel($TOTK->channel_ids['staff_bot'])) $channel->sendMessage('Updating code from GitHub...');
            $return = 'updating code';
            break;
        
        case 'update':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('composer update');
            $TOTK->logger->info('[COMPOSER UPDATE]');
            if (isset($TOTK->channel_ids['staff_bot']) && $channel = $TOTK->discord->getChannel($TOTK->channel_ids['staff_bot'])) $channel->sendMessage('Updating dependencies...');
            $return = 'updating dependencies';
            break;
        
        case 'restart':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            $TOTK->logger->info('[RESTART]');
            if (isset($TOTK->channel_ids['staff_bot']) && $channel = $TOTK->discord->getChannel($TOTK->channel_ids['staff_bot'])) $channel->sendMessage('Restarting...');
            $return = 'restarting';
            $socket->close();
            $TOTK->discord->getLoop()->addTimer(5, function () use ($TOTK) {
                \restart();
                $TOTK->discord->close();
                die();
            });
            break;

        case 'lookup':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if (! $id || !webapiSnow($id) || !$return = $TOTK->discord->users->get('id', $id)) return webapiFail('user_id', $id);
            break;

        case 'owner':
            if (! $whitelisted) {
                $TOTK->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if (! $id || !webapiSnow($id)) return webapiFail('user_id', $id); $return = false;
            if ($user = $TOTK->discord->users->get('id', $id)) { //Search all guilds the bot is in and check if the user id exists as a guild owner
                foreach ($TOTK->discord->guilds as $guild) {
                    if ($id == $guild->owner_id) {
                        $return = true;
                        break 1;
                    }
                }
            }
            break;

        case 'avatar':
            if (! $id || !webapiSnow($id)) return webapiFail('user_id', $id);
            if (! $user = $TOTK->discord->users->get('id', $id)) $return = 'https://cdn.discordapp.com/embed/avatars/'.rand(0,4).'.png';
            else $return = $user->avatar;
            //if (! $return) return new Response(($id ? 404 : 400), ['Content-Type' => 'text/plain'], (''));
            break;
            
        default:
            return new Response(501, ['Content-Type' => 'text/plain'], 'Not implemented');
    }
    return new Response(200, ['Content-Type' => 'text/json'], json_encode($return));
});
$webapi->listen($socket);
$webapi->on('error', function ($e) use ($TOTK) {
    $TOTK->logger->error('API ' . $e->getMessage());
});