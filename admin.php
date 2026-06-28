<?php
/**
 * Blood Solution — Hardened Admin Panel  v3.3.0
 * NEW: Call Log Delete | Bulk Delete | Token Manager | IP Whitelist | Password Change
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// ── 1. SECURITY HEADERS ─────────────────────────────────
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:;");
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
         || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if($isHttps) header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// ── 2. CONFIG ────────────────────────────────────────────
$config_file = __DIR__ . '/admin_config.php';
if(!file_exists($config_file)){ ob_end_clean(); header('Location: admin_setup.php'); exit(); }
include_once $config_file;

// Pull shared site config (SITE_URL etc.) so push-notification URLs stay in one
// place — change the domain only in config.php. Fallback keeps admin working if
// config.php is ever absent.
if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';
if (!defined('SITE_URL')) define('SITE_URL', 'https://bloodarenabd.tech');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_SECONDS',    900);
define('SESSION_IDLE_LIMIT', 1800);
define('SESSION_HARD_LIMIT', 14400);

// ── 3. SESSION ───────────────────────────────────────────
$domain = strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>$domain,'secure'=>$isHttps,'httponly'=>true,'samesite'=>'Strict']);
ini_set('session.use_strict_mode',1); ini_set('session.use_only_cookies',1);
ini_set('session.cookie_httponly',1); ini_set('session.use_trans_sid',0);
ini_set('session.sid_length',48);     ini_set('session.sid_bits_per_character',6);
session_start();

// ── 4. DB ────────────────────────────────────────────────
$conn=null; $db_error='';
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    if(file_exists(__DIR__.'/db.php')){
        include_once __DIR__.'/db.php';
        if(isset($conn)&&$conn instanceof mysqli){ $conn->set_charset("utf8mb4"); }
        else { $db_error='DB connection failed.'; $conn=null; }
    } else { $db_error='db.php not found.'; }
} catch(Throwable $e){ $db_error='DB error.'; $conn=null; }

// ── 5. HELPERS ───────────────────────────────────────────
function esc($v){ return htmlspecialchars($v??'',ENT_QUOTES|ENT_HTML5,'UTF-8'); }
function dbq($c,$s){ if(!$c) return null; $r=$c->query($s); return $r?:null; }
// Delete attached document files (from disk) AND their DB rows for the given
// blood_request IDs. (No FK/CASCADE — request_documents rows must be removed
// explicitly here, otherwise they'd be orphaned when the request row is deleted.)
function delReqDocFiles($conn, array $ids){
    if(!$conn || !defined('UPLOAD_DIR')) return;
    $ids=array_values(array_filter(array_map('intval',$ids),fn($x)=>$x>0));
    if(!$ids) return;
    $in=implode(',',$ids);
    $res=dbq($conn,"SELECT file_path FROM request_documents WHERE request_id IN ($in)");
    if($res){
        $base=rtrim(UPLOAD_DIR,"/\\"); $broot=realpath(UPLOAD_DIR);
        while($d=$res->fetch_assoc()){
            $real=realpath($base.'/'.$d['file_path']);
            if($real && $broot && strncmp($real,$broot,strlen($broot))===0 && is_file($real)){ @unlink($real); }
        }
    }
    // remove the rows too (CASCADE is not used)
    dbq($conn,"DELETE FROM request_documents WHERE request_id IN ($in)");
}
// PHP 7.x-safe COUNT(*) helper (replaces the PHP 8.0+ nullsafe-operator fetch pattern)
function _cnt($res){ if($res instanceof mysqli_result){ $row=$res->fetch_assoc(); return isset($row['c'])?$row['c']:0; } return 0; }

function ensureConn(&$conn, &$db_error){
    if($conn instanceof mysqli){ if(@$conn->ping()) return true; $conn=null; }
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        if(file_exists(__DIR__.'/db.php')){
            $tmp=null;
            (function() use (&$tmp){ global $conn; include __DIR__.'/db.php'; $tmp=$conn; })();
            if($tmp instanceof mysqli){ $tmp->set_charset("utf8mb4"); $conn=$tmp; return true; }
        }
    } catch(Throwable $e){}
    $db_error='DB reconnect failed.';
    return false;
}
function getIP(){
    foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k){
        if(!empty($_SERVER[$k])){ $ip=trim(explode(',',$_SERVER[$k])[0]); if(filter_var($ip,FILTER_VALIDATE_IP)) return $ip; }
    }
    return 'unknown';
}
function sessionFingerprint(){ return hash('sha256', getIP().'|'.($_SERVER['HTTP_USER_AGENT']??'')); }

function generateToken(){
    return 'BST-'.strtoupper(bin2hex(random_bytes(16)));
}

// ── 6. AUDIT LOG ─────────────────────────────────────────
function ensureAuditTable($conn){
    if(!$conn) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_audit_log`(`id` INT AUTO_INCREMENT PRIMARY KEY,`event` VARCHAR(80) NOT NULL,`ip` VARCHAR(50) NOT NULL,`user_agent` VARCHAR(300) DEFAULT NULL,`detail` VARCHAR(300) DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function auditLog($conn,$event,$detail=''){
    if(!$conn) return;
    ensureAuditTable($conn);
    $ip=mysqli_real_escape_string($conn,getIP());
    $ua=mysqli_real_escape_string($conn,mb_substr($_SERVER['HTTP_USER_AGENT']??'',0,300));
    $ev=mysqli_real_escape_string($conn,$event);
    $dt=mysqli_real_escape_string($conn,$detail);
    $conn->query("INSERT INTO admin_audit_log (event,ip,user_agent,detail) VALUES ('$ev','$ip','$ua','$dt')");
}

// ── 7. BRUTE-FORCE ───────────────────────────────────────
function getBruteKey(){ return 'bf_'.hash('sha256',getIP()); }
function isLockedOut(){
    $k=getBruteKey(); if(!isset($_SESSION[$k])) return false;
    $b=$_SESSION[$k];
    if($b['count']>=MAX_LOGIN_ATTEMPTS){ if(time()-$b['since']<LOCKOUT_SECONDS) return true; unset($_SESSION[$k]); }
    return false;
}
function recordFailedAttempt(){
    $k=getBruteKey(); if(!isset($_SESSION[$k])) $_SESSION[$k]=['count'=>0,'since'=>time()];
    if(time()-$_SESSION[$k]['since']>=LOCKOUT_SECONDS) $_SESSION[$k]=['count'=>0,'since'=>time()];
    $_SESSION[$k]['count']++;
}
function clearFailedAttempts(){ unset($_SESSION[getBruteKey()]); }
function attemptsLeft(){ $k=getBruteKey(); if(!isset($_SESSION[$k])) return MAX_LOGIN_ATTEMPTS; return max(0,MAX_LOGIN_ATTEMPTS-$_SESSION[$k]['count']); }
function lockoutSecondsLeft(){ $k=getBruteKey(); if(!isset($_SESSION[$k])) return 0; return max(0,LOCKOUT_SECONDS-(time()-$_SESSION[$k]['since'])); }

// ── 8. CSRF ──────────────────────────────────────────────
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
function checkCSRF(){
    if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); die(json_encode(['ok'=>false,'msg'=>'Method not allowed.'])); }
    $token=$_POST['csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??'');
    if(!hash_equals($_SESSION['csrf']??'',$token)){ http_response_code(403); die(json_encode(['ok'=>false,'msg'=>'CSRF failed.'])); }
}

// ── 9. SESSION VALIDATION ────────────────────────────────
function validateSession(){
    if(empty($_SESSION['adm'])) return false;
    if(isset($_SESSION['fp'])&&$_SESSION['fp']!==sessionFingerprint()){ session_destroy(); return false; }
    if(isset($_SESSION['last_active'])&&(time()-$_SESSION['last_active'])>SESSION_IDLE_LIMIT){ session_destroy(); return false; }
    if(isset($_SESSION['adm_start'])&&(time()-$_SESSION['adm_start'])>SESSION_HARD_LIMIT){ session_destroy(); return false; }
    $_SESSION['last_active']=time(); return true;
}

// ── 10. IP WHITELIST CHECK ───────────────────────────────
function ensureAdminTables($conn){
    if(!$conn) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_settings`(`setting_key` VARCHAR(80) PRIMARY KEY,`setting_value` TEXT DEFAULT NULL,`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `ip_whitelist`(`id` INT AUTO_INCREMENT PRIMARY KEY,`ip` VARCHAR(50) NOT NULL,`label` VARCHAR(100) DEFAULT '',`is_active` TINYINT(1) DEFAULT 1,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `api_tokens`(`id` INT AUTO_INCREMENT PRIMARY KEY,`token_name` VARCHAR(100) NOT NULL,`token_value` VARCHAR(80) NOT NULL,`is_active` TINYINT(1) DEFAULT 1,`last_used` TIMESTAMP NULL DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_users`(`id` INT AUTO_INCREMENT PRIMARY KEY,`username` VARCHAR(60) NOT NULL UNIQUE,`pass_hash` VARCHAR(255) NOT NULL,`role` ENUM('super_admin','moderator') NOT NULL DEFAULT 'moderator',`is_active` TINYINT(1) DEFAULT 1,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_messages`(`id` INT AUTO_INCREMENT PRIMARY KEY,`sender_name` VARCHAR(100) NOT NULL,`sender_phone` VARCHAR(20) NOT NULL,`message` TEXT NOT NULL,`device_id` VARCHAR(100) NOT NULL,`is_read` TINYINT DEFAULT 0,`admin_reply` TEXT DEFAULT NULL,`replied_at` TIMESTAMP NULL DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Default settings
    $conn->query("INSERT IGNORE INTO admin_settings (setting_key,setting_value) VALUES ('ip_whitelist_enabled','0')");
}

function checkIpWhitelist($conn){
    if(!$conn) return; // DB unavailable — allow through
    ensureAdminTables($conn);
    $res=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='ip_whitelist_enabled' LIMIT 1");
    if(!$res) return;
    $row=$res->fetch_assoc();
    if(($row['setting_value']??'0')!=='1') return; // Not enabled
    $current_ip=getIP();
    $esc_ip=mysqli_real_escape_string($conn,$current_ip);
    $check=$conn->query("SELECT id FROM ip_whitelist WHERE ip='$esc_ip' AND is_active=1 LIMIT 1");
    if(!$check||$check->num_rows===0){
        ob_end_clean();
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>403 Access Denied</title><style>*{margin:0;padding:0;box-sizing:border-box;}body{background:#0f1115;color:#ef4444;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;flex-direction:column;gap:14px;text-align:center;padding:20px;}.box{background:#1a1d24;border:1px solid rgba(239,68,68,.25);border-radius:16px;padding:36px;max-width:400px;}.icon{font-size:3rem;margin-bottom:12px;}.ip{font-family:monospace;font-size:.85em;color:#6b7280;margin-top:8px;background:#111;padding:4px 12px;border-radius:8px;display:inline-block;}</style></head><body><div class="box"><div class="icon">🚫</div><h2>Access Denied</h2><p style="color:#9ca3af;font-size:.9em;margin-top:8px;">আপনার IP address এই panel access করার অনুমতি নেই।</p><div class="ip">IP: '.htmlspecialchars($current_ip).'</div></div><!-- 🌐 i18n: dictionary + standalone engine (admin language toggle) -->';
        echo '<script>'; include __DIR__ . '/assets/i18n-dict.js.php'; echo '</script>';
        echo '<script>'; include __DIR__ . '/assets/i18n-engine.js.php'; echo '</script>';
        echo '</body></html>';
        exit();
    }
}

// Run IP whitelist check before anything else (except AJAX if already in session)
checkIpWhitelist($conn);

// ── 11. LOGOUT ───────────────────────────────────────────
if(isset($_GET['logout'])||isset($_POST['logout'])){
    if(isset($_POST['logout'])){ $tok=$_POST['csrf']??''; if(!hash_equals($_SESSION['csrf']??'',$tok)){ http_response_code(403); die('Forbidden'); } }
    auditLog($conn,'LOGOUT');
    session_unset(); session_destroy();
    header('Location: admin.php'); exit();
}

// ── 12. LOGIN ────────────────────────────────────────────
$login_error=''; $locked=isLockedOut(); $lockout_left=$locked?lockoutSecondsLeft():0;
if(!$locked && isset($_POST['admin_login'])){
    checkCSRF();
    if(!empty($_POST['website'])){ auditLog($conn,'BOT_TRAP','honeypot'); sleep(3); die(''); }
    $pass_input=$_POST['pass']??'';
    $uname_input=trim($_POST['username']??'');

    $logged_as_role = null;

    // ── Super admin login (legacy — no username, uses ADMIN_HASH from config)
    if(empty($uname_input)){
        if(defined('ADMIN_HASH')&&password_verify($pass_input,ADMIN_HASH)){
            $logged_as_role='super_admin';
        }
    }

    // ── Moderator / admin_users table login
    if($logged_as_role===null && $conn && !empty($uname_input)){
        if($conn) ensureAdminTables($conn);
        $esc_u=mysqli_real_escape_string($conn,$uname_input);
        $ur=$conn->query("SELECT id,pass_hash,role,is_active FROM admin_users WHERE username='$esc_u' LIMIT 1");
        $urow=$ur?$ur->fetch_assoc():null;
        if($urow && (int)$urow['is_active']===1 && password_verify($pass_input,$urow['pass_hash'])){
            $logged_as_role=$urow['role'];
        }
    }

    if($logged_as_role !== null){
        clearFailedAttempts(); session_regenerate_id(true);
        $_SESSION['adm']=true; $_SESSION['adm_start']=time(); $_SESSION['last_active']=time();
        $_SESSION['fp']=sessionFingerprint(); $_SESSION['csrf']=bin2hex(random_bytes(32));
        $_SESSION['adm_role']=$logged_as_role;
        auditLog($conn,'LOGIN_SUCCESS',"role:$logged_as_role uname:$uname_input");
        header('Location: admin.php'); exit();
    } else {
        recordFailedAttempt(); auditLog($conn,'LOGIN_FAIL','attempts_left:'.attemptsLeft().' uname:'.$uname_input);
        $left=attemptsLeft();
        $login_error=$left===0?'🔒 আপনাকে '.ceil(LOCKOUT_SECONDS/60).' মিনিটের জন্য block করা হয়েছে!':'❌ Wrong credentials! আর '.$left.' টি সুযোগ বাকি।';
        if($left===0) $locked=true;
        sleep(1);
    }
}

$logged_in=validateSession();
// Role detection — default: super_admin for legacy sessions (no role set)
$adm_role     = $_SESSION['adm_role'] ?? 'super_admin';
$is_super     = ($adm_role === 'super_admin');
$is_moderator = ($adm_role === 'moderator');

// Helper: block moderators from destructive actions
function requireSuperAdmin(){
    global $is_super;
    if(!$is_super){ echo json_encode(['ok'=>false,'msg'=>'🚫 Super Admin only.']); exit(); }
}

if($logged_in&&!isset($_POST['act'])&&!isset($_POST['admin_login'])){
    if(empty($_SERVER['HTTP_X_CSRF_TOKEN'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
}

// ── 13. AJAX ACTIONS ─────────────────────────────────────
if($logged_in && isset($_POST['act'])){
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    checkCSRF();
    ensureConn($conn, $db_error);

    $act=$_POST['act']; $id=(int)($_POST['id']??0);
    $allowed=[
        'del_donor','del_req','fulfill_req','del_report','del_call','del_community_post',
        'del_multiple',
        'adv_search','edit_donor','get_donor',
        'add_token','del_token','toggle_token',
        'add_ip','del_ip','toggle_ip_whitelist',
        'change_password',
        'get_settings',
        'send_notif_bulk','send_notif_donor','send_notif_selected_donors','run_auto_reminder',
        'send_fcm_push',
        'get_inbox','reply_inbox_msg','mark_inbox_read',
        'del_inbox_msg','clear_inbox',
        'get_self_reports','revoke_self_report',
        'add_moderator','del_moderator','list_moderators',
        'get_admin_poll',
        'clear_service_notifs',
        'cfg_unlock','cfg_get','cfg_save','cfg_change_pass','cfg_reset',
        'sms_get','sms_save','sms_test'
    ];
    if(!in_array($act,$allowed,true)){ echo json_encode(['ok'=>false,'msg'=>'Unknown action']); exit(); }

    // ── Role-based blocks: moderator CANNOT do these ─────
    $super_only_acts = [
        'del_donor','del_req','del_report','del_call','del_community_post','del_multiple',
        'del_token','del_ip',
        'change_password','toggle_ip_whitelist',
        'add_token','add_ip',
        'reply_inbox_msg',  // moderator cannot reply messages
        'revoke_self_report',  // moderator cannot revoke a donation count
        'add_moderator','del_moderator',
        'cfg_unlock','cfg_get','cfg_save','cfg_change_pass','cfg_reset',  // Site Config = super admin only
        'sms_get','sms_save','sms_test'
    ];
    if($is_moderator && in_array($act,$super_only_acts,true)){
        echo json_encode(['ok'=>false,'msg'=>'🚫 এই কাজটি শুধুমাত্র Super Admin করতে পারবে।']); exit();
    }

    // ── Get donor for edit ───────────────────────────────
    if($act==='get_donor' && $conn && $id>0){
        $stmt=$conn->prepare("SELECT id,name,phone,blood_group,location,last_donation,willing_to_donate,total_donations,reg_geo FROM donors WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute();
        $r=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if($r){
            $ld=$r['last_donation']??'no';
            $r['last_donation_fmt']=($ld==='no'||empty($ld)||$ld==='0000-00-00')?'no':date('d/m/Y',strtotime($ld));
            $geo=$r['reg_geo']??''; $r['geo_lat']=''; $r['geo_lng']='';
            if(preg_match('/Lat:\s*([\-0-9.]+),\s*Lon:\s*([\-0-9.]+)/',$geo,$gm)){ $r['geo_lat']=$gm[1]; $r['geo_lng']=$gm[2]; }
            unset($r['reg_geo']);
            echo json_encode(['ok'=>true,'donor'=>$r]);
        } else { echo json_encode(['ok'=>false,'msg'=>'Donor not found']); }
        exit();
    }

    // ── Edit donor ───────────────────────────────────────
    if($act==='edit_donor' && $conn && $id>0){
        $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??'');
        $blood_group=trim($_POST['blood_group']??''); $location=trim($_POST['location']??'');
        $last_raw=trim($_POST['last_donation']??'no'); $willing=trim($_POST['willing_to_donate']??'yes');
        $total_don=max(0,(int)($_POST['total_donations']??0)); $reg_geo_new=trim($_POST['reg_geo']??'');
        if(!$name||!$phone||!$location){ echo json_encode(['ok'=>false,'msg'=>'নাম, ফোন ও লোকেশন দিতে হবে।']); exit(); }
        if(!preg_match('/^[\p{Bengali}a-zA-Z\s\.]+$/u',$name)){ echo json_encode(['ok'=>false,'msg'=>'নামে অবৈধ অক্ষর।']); exit(); }
        if(!in_array($blood_group,['A+','A-','B+','B-','AB+','AB-','O+','O-'],true)){ echo json_encode(['ok'=>false,'msg'=>'Invalid blood group.']); exit(); }
        if(!in_array($willing,['yes','no'],true)) $willing='yes';
        $last_to_save='no';
        if(strtolower($last_raw)!=='no'&&!empty($last_raw)){
            $d=DateTime::createFromFormat('d/m/Y',$last_raw);
            if(!$d||$d->format('d/m/Y')!==$last_raw){ echo json_encode(['ok'=>false,'msg'=>'Date format ভুল। dd/mm/yyyy ব্যবহার করুন।']); exit(); }
            $fmt=$d->format('Y-m-d');
            if($fmt>date('Y-m-d')||(int)$d->format('Y')<1940){ echo json_encode(['ok'=>false,'msg'=>'Invalid date.']); exit(); }
            $last_to_save=$fmt;
        }
        $badge_level=$total_don>=10?'Legend':($total_don>=5?'Hero':($total_don>=2?'Active':'New'));
        if(!empty($reg_geo_new)){
            $stmt=$conn->prepare("UPDATE donors SET name=?,phone=?,blood_group=?,location=?,last_donation=?,willing_to_donate=?,total_donations=?,badge_level=?,reg_geo=? WHERE id=?");
            $stmt->bind_param("sssssssssi",$name,$phone,$blood_group,$location,$last_to_save,$willing,$total_don,$badge_level,$reg_geo_new,$id);
        } else {
            $stmt=$conn->prepare("UPDATE donors SET name=?,phone=?,blood_group=?,location=?,last_donation=?,willing_to_donate=?,total_donations=?,badge_level=? WHERE id=?");
            $stmt->bind_param("ssssssssi",$name,$phone,$blood_group,$location,$last_to_save,$willing,$total_don,$badge_level,$id);
        }
        if($stmt->execute()){ auditLog($conn,'EDIT_DONOR',"id:$id name:$name"); echo json_encode(['ok'=>true,'msg'=>'✅ Donor updated!']); }
        else { echo json_encode(['ok'=>false,'msg'=>'Update failed: '.($conn->error??'')]); }
        $stmt->close(); exit();
    }

    // ── Advanced Search ──────────────────────────────────
    if($act==='adv_search' && $conn){
        $name=trim($_POST['s_name']??''); $phone=trim($_POST['s_phone']??'');
        $group=trim($_POST['s_group']??'All'); $loc=trim($_POST['s_loc']??'');
        $status=trim($_POST['s_status']??'All'); $badge=trim($_POST['s_badge']??'All');
        $from=trim($_POST['s_from']??''); $to=trim($_POST['s_to']??'');
        $vg=["A+","A-","B+","B-","AB+","AB-","O+","O-","All"]; if(!in_array($group,$vg,true)) $group='All';
        $vb=["New","Active","Hero","Legend","All"]; if(!in_array($badge,$vb,true)) $badge='All';
        $vs=["All","Available","Not Available","Not Willing"]; if(!in_array($status,$vs,true)) $status='All';
        $parts=[]; $params=[]; $types='';
        if($name!==''){     $parts[]="name LIKE ?";         $params[]="%$name%"; $types.='s'; }
        if($phone!==''){    $parts[]="phone LIKE ?";        $params[]="%$phone%"; $types.='s'; }
        if($group!=='All'){ $parts[]="blood_group=?";       $params[]=$group; $types.='s'; }
        if($loc!==''){      $parts[]="location LIKE ?";     $params[]="%$loc%"; $types.='s'; }
        if($badge!=='All'){ $parts[]="badge_level=?";       $params[]=$badge; $types.='s'; }
        if($from!==''){     $parts[]="DATE(created_at)>=?"; $params[]=$from; $types.='s'; }
        if($to!==''){       $parts[]="DATE(created_at)<=?"; $params[]=$to; $types.='s'; }
        if($status==='Available')     $parts[]="(willing_to_donate='yes' AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120))";
        elseif($status==='Not Willing') $parts[]="willing_to_donate='no'";
        elseif($status==='Not Available') $parts[]="(willing_to_donate='yes' AND last_donation!='no' AND last_donation!='' AND last_donation!='0000-00-00' AND DATEDIFF(CURDATE(),last_donation)<120)";
        $where=count($parts)?'WHERE '.implode(' AND ',$parts):'';
        $sql="SELECT id,name,blood_group,phone,location,last_donation,willing_to_donate,badge_level,total_donations,created_at FROM donors $where ORDER BY id DESC LIMIT 200";
        $stmt=$conn->prepare($sql);
        if($types) $stmt->bind_param($types,...$params);
        $stmt->execute(); $res=$stmt->get_result();
        $rows=[];
        while($row=$res->fetch_assoc()){
            $ld=$row['last_donation']??'no'; $w=$row['willing_to_donate']??'yes';
            if($w==='no') $st='Not Willing';
            elseif($ld==='no'||empty($ld)||$ld==='0000-00-00'||(strtotime($ld)&&(time()-strtotime($ld))/86400>=120)) $st='Available';
            else $st='Not Available';
            $row['_status']=$st;
            $rows[]=$row;
        }
        $stmt->close();
        auditLog($conn,'ADV_SEARCH',"name:$name grp:$group st:$status");
        echo json_encode(['ok'=>true,'rows'=>$rows,'count'=>count($rows)]); exit();
    }



    // ── Token Management ─────────────────────────────────
    if($act==='add_token' && $conn){
        $tname=trim($_POST['token_name']??'');
        if(empty($tname)){ echo json_encode(['ok'=>false,'msg'=>'Token name দিন।']); exit(); }
        $tname=mb_substr($tname,0,100);
        $tval=generateToken();
        $stmt=$conn->prepare("INSERT INTO api_tokens (token_name,token_value,is_active) VALUES (?,?,1)");
        $stmt->bind_param("ss",$tname,$tval); $stmt->execute();
        $newId=(int)$conn->insert_id; $stmt->close();
        auditLog($conn,'ADD_TOKEN',"name:$tname");
        echo json_encode(['ok'=>true,'id'=>$newId,'token_name'=>$tname,'token_value'=>$tval,'is_active'=>1,'created_at'=>date('d M Y, h:i A')]); exit();
    }

    if($act==='del_token' && $conn && $id>0){
        $stmt=$conn->prepare("DELETE FROM api_tokens WHERE id=?");
        $stmt->bind_param("i",$id); $exec=$stmt->execute(); $aff=$stmt->affected_rows; $stmt->close();
        if($exec&&$aff>0){ auditLog($conn,'DEL_TOKEN',"id:$id"); echo json_encode(['ok'=>true]); }
        else { echo json_encode(['ok'=>false,'msg'=>'Token পাওয়া যায়নি।']); }
        exit();
    }

    if($act==='toggle_token' && $conn && $id>0){
        $stmt=$conn->prepare("UPDATE api_tokens SET is_active=(1-is_active) WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
        $r=$conn->query("SELECT is_active FROM api_tokens WHERE id=$id");
        $row=$r?$r->fetch_assoc():null;
        echo json_encode(['ok'=>true,'is_active'=>(int)($row['is_active']??0)]); exit();
    }

    // ── IP Whitelist ─────────────────────────────────────
    if($act==='add_ip' && $conn){
        $ip=trim($_POST['ip_addr']??'');
        $label=mb_substr(trim($_POST['ip_label']??''),0,100);
        if(!filter_var($ip,FILTER_VALIDATE_IP)){ echo json_encode(['ok'=>false,'msg'=>'সঠিক IP address দিন।']); exit(); }
        // Check duplicate
        $esc=mysqli_real_escape_string($conn,$ip);
        $ex=$conn->query("SELECT id FROM ip_whitelist WHERE ip='$esc' LIMIT 1");
        if($ex&&$ex->num_rows>0){ echo json_encode(['ok'=>false,'msg'=>'এই IP আগেই আছে।']); exit(); }
        $stmt=$conn->prepare("INSERT INTO ip_whitelist (ip,label,is_active) VALUES (?,?,1)");
        $stmt->bind_param("ss",$ip,$label); $stmt->execute();
        $newId=(int)$conn->insert_id; $stmt->close();
        auditLog($conn,'ADD_IP',"ip:$ip label:$label");
        echo json_encode(['ok'=>true,'id'=>$newId,'ip'=>$ip,'label'=>$label,'is_active'=>1,'created_at'=>date('d M Y, h:i A')]); exit();
    }

    if($act==='del_ip' && $conn && $id>0){
        $stmt=$conn->prepare("DELETE FROM ip_whitelist WHERE id=?");
        $stmt->bind_param("i",$id); $exec=$stmt->execute(); $aff=$stmt->affected_rows; $stmt->close();
        if($exec&&$aff>0){ auditLog($conn,'DEL_IP',"id:$id"); echo json_encode(['ok'=>true]); }
        else { echo json_encode(['ok'=>false,'msg'=>'IP পাওয়া যায়নি।']); }
        exit();
    }

    if($act==='toggle_ip_whitelist' && $conn){
        $cur=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='ip_whitelist_enabled' LIMIT 1");
        $curRow=$cur?$cur->fetch_assoc():null;
        $newVal=($curRow&&$curRow['setting_value']==='1')?'0':'1';
        $conn->query("INSERT INTO admin_settings (setting_key,setting_value) VALUES ('ip_whitelist_enabled','$newVal') ON DUPLICATE KEY UPDATE setting_value='$newVal'");
        auditLog($conn,'TOGGLE_IP_WL',"enabled:$newVal");
        echo json_encode(['ok'=>true,'enabled'=>$newVal==='1']); exit();
    }

    // ── Change Password ──────────────────────────────────
    if($act==='change_password'){
        $current_pass=trim($_POST['current_pass']??'');
        $new_pass=trim($_POST['new_pass']??'');
        $confirm_pass=trim($_POST['confirm_pass']??'');
        if(!defined('ADMIN_HASH')||!password_verify($current_pass,ADMIN_HASH)){
            echo json_encode(['ok'=>false,'msg'=>'বর্তমান password ভুল।']); exit();
        }
        if(strlen($new_pass)<8){ echo json_encode(['ok'=>false,'msg'=>'নতুন password কমপক্ষে ৮ অক্ষরের হতে হবে।']); exit(); }
        if($new_pass!==$confirm_pass){ echo json_encode(['ok'=>false,'msg'=>'নতুন password ও confirm password মিলছে না।']); exit(); }
        $newHash=password_hash($new_pass,PASSWORD_BCRYPT,['cost'=>12]);
        // Write new hash to admin_config.php
        $configPath=__DIR__.'/admin_config.php';
        $configContent=file_get_contents($configPath);
        if($configContent===false){ echo json_encode(['ok'=>false,'msg'=>'Config file পড়তে পারছি না।']); exit(); }
        $newConfig=preg_replace("/define\s*\(\s*'ADMIN_HASH'\s*,\s*'[^']+'\s*\)/","define('ADMIN_HASH','$newHash')",$configContent);
        if($newConfig===null||$newConfig===$configContent){
            // Try double-quote version
            $newConfig=preg_replace('/define\s*\(\s*"ADMIN_HASH"\s*,\s*"[^"]+"\s*\)/',"define('ADMIN_HASH','$newHash')",$configContent);
        }
        if(!$newConfig||$newConfig===$configContent){ echo json_encode(['ok'=>false,'msg'=>'Config update করতে পারছি না। ADMIN_HASH define পাওয়া যায়নি।']); exit(); }
        if(file_put_contents($configPath,$newConfig)===false){ echo json_encode(['ok'=>false,'msg'=>'Config file লিখতে পারছি না।']); exit(); }
        auditLog($conn,'CHANGE_PASSWORD','password updated');
        echo json_encode(['ok'=>true,'msg'=>'✅ Password সফলভাবে পরিবর্তন হয়েছে!']); exit();
    }

    // ── Get Settings ─────────────────────────────────────
    if($act==='get_settings' && $conn){
        ensureAdminTables($conn);
        $wl=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='ip_whitelist_enabled' LIMIT 1");
        $wlRow=$wl?$wl->fetch_assoc():null;
        $enabled=($wlRow&&$wlRow['setting_value']==='1');
        $ips=[]; $ir=$conn->query("SELECT * FROM ip_whitelist ORDER BY id DESC");
        if($ir) while($r=$ir->fetch_assoc()) $ips[]=$r;
        $tokens=[]; $tr=$conn->query("SELECT id,token_name,token_value,is_active,created_at,last_used FROM api_tokens ORDER BY id DESC");
        if($tr) while($r=$tr->fetch_assoc()) $tokens[]=$r;
        echo json_encode(['ok'=>true,'ip_whitelist_enabled'=>$enabled,'ips'=>$ips,'tokens'=>$tokens]); exit();
    }

    // ════════════════════════════════════════════════════════════════
    //  SITE CONFIG EDITOR (UI-editable config — writes config_overrides.json)
    //  নিজস্ব password gate; default 'alif2005@A' (bcrypt hashed, DB-তে রাখা)।
    //  config.php কখনো rewrite হয় না — শুধু data-only JSON overlay লেখা হয়।
    // ════════════════════════════════════════════════════════════════
    if(in_array($act,['cfg_unlock','cfg_get','cfg_save','cfg_change_pass','cfg_reset'],true)){
        ensureAdminTables($conn);

        // editor password helpers (stored in admin_settings as bcrypt hash) ──
        $cfg_get_hash = function() use ($conn) {
            $r=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='cfg_editor_hash' LIMIT 1");
            $row=$r?$r->fetch_assoc():null;
            if($row && !empty($row['setting_value'])) return $row['setting_value'];
            // first run — seed default password 'alif2005@A' (hashed)
            $h=password_hash('alif2005@A',PASSWORD_BCRYPT,['cost'=>12]);
            $he=mysqli_real_escape_string($conn,$h);
            $conn->query("INSERT INTO admin_settings (setting_key,setting_value) VALUES ('cfg_editor_hash','$he') ON DUPLICATE KEY UPDATE setting_value='$he'");
            return $h;
        };
        // session unlock flag (per admin session) ──
        $cfg_is_unlocked = function() { return !empty($_SESSION['cfg_unlocked']); };

        // editable-key list comes from config.php's exposed defaults ──
        $defaults = $GLOBALS['CFG_DEFAULTS'] ?? [];
        $effective = $GLOBALS['CFG_EFFECTIVE'] ?? $defaults;

        // ── Unlock (verify editor password) ──────────────────────
        if($act==='cfg_unlock'){
            $pass=(string)($_POST['cfg_pass']??'');
            if(!password_verify($pass,$cfg_get_hash())){
                auditLog($conn,'CFG_UNLOCK_FAIL','wrong editor password');
                echo json_encode(['ok'=>false,'msg'=>'❌ ভুল password।']); exit();
            }
            $_SESSION['cfg_unlocked']=true;
            auditLog($conn,'CFG_UNLOCK','config editor unlocked');
            echo json_encode(['ok'=>true]); exit();
        }

        // all remaining cfg_* actions require an unlocked session ──
        if(!$cfg_is_unlocked()){
            echo json_encode(['ok'=>false,'locked'=>true,'msg'=>'🔒 আগে Site Config password দিন।']); exit();
        }

        // ── Get current effective values (for rendering the form) ─
        if($act==='cfg_get'){
            // never ship nothing; secrets are shown so they can be edited (super-admin only, already gated)
            echo json_encode(['ok'=>true,'defaults'=>$defaults,'values'=>$effective,
                'overlay_exists'=>is_file(CFG_OVERLAY_FILE)]); exit();
        }

        // ── Save (merge posted values → config_overrides.json) ───
        if($act==='cfg_save'){
            $payload=$_POST['cfg']??[];
            if(is_string($payload)){ $payload=json_decode($payload,true); }
            if(!is_array($payload)){ echo json_encode(['ok'=>false,'msg'=>'Invalid data.']); exit(); }

            // Build a clean overlay using ONLY known keys, coerced to default types.
            $overlay=[];
            foreach($defaults as $k=>$def){
                if($k==='FIREBASE'){
                    if(isset($payload['FIREBASE']) && is_array($payload['FIREBASE'])){
                        $fb=[];
                        foreach($def as $fk=>$fv){
                            if(isset($payload['FIREBASE'][$fk])){
                                $v=trim((string)$payload['FIREBASE'][$fk]);
                                if($v!=='') $fb[$fk]=$v;
                            }
                        }
                        if($fb) $overlay['FIREBASE']=$fb;
                    }
                    continue;
                }
                if(!array_key_exists($k,$payload)) continue;
                $v=$payload[$k];
                if(is_bool($def))        $overlay[$k]=($v==='1'||$v===1||$v===true||$v==='true');
                elseif(is_int($def))     $overlay[$k]=(int)$v;
                else                     $overlay[$k]=trim((string)$v);
            }

            // basic sanity: SITE_URL must look like a URL if provided
            if(isset($overlay['SITE_URL']) && $overlay['SITE_URL']!=='' && !preg_match('~^https?://~i',$overlay['SITE_URL'])){
                echo json_encode(['ok'=>false,'msg'=>'SITE_URL অবশ্যই http(s):// দিয়ে শুরু হতে হবে।']); exit();
            }

            $json=json_encode($overlay,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if($json===false){ echo json_encode(['ok'=>false,'msg'=>'JSON encode ব্যর্থ।']); exit(); }
            // atomic write — temp then rename, so a half-write can't corrupt the live file
            $tmp=CFG_OVERLAY_FILE.'.tmp';
            if(@file_put_contents($tmp,$json)===false || !@rename($tmp,CFG_OVERLAY_FILE)){
                @unlink($tmp);
                echo json_encode(['ok'=>false,'msg'=>'❌ config_overrides.json লিখতে পারছি না — folder write permission চেক করুন।']); exit();
            }
            auditLog($conn,'CFG_SAVE','keys:'.implode(',',array_keys($overlay)));
            echo json_encode(['ok'=>true,'msg'=>'✅ Config সংরক্ষিত! পরিবর্তন এখনই কার্যকর।']); exit();
        }

        // ── Reset to defaults (delete overlay file) ──────────────
        if($act==='cfg_reset'){
            if(is_file(CFG_OVERLAY_FILE) && !@unlink(CFG_OVERLAY_FILE)){
                echo json_encode(['ok'=>false,'msg'=>'❌ overlay file মুছতে পারছি না।']); exit();
            }
            auditLog($conn,'CFG_RESET','overlay removed — back to defaults');
            echo json_encode(['ok'=>true,'msg'=>'✅ সব default-এ ফিরে গেছে।']); exit();
        }

        // ── Change editor password ───────────────────────────────
        if($act==='cfg_change_pass'){
            $cur=(string)($_POST['cur_pass']??'');
            $new=(string)($_POST['new_pass']??'');
            $cnf=(string)($_POST['confirm_pass']??'');
            if(!password_verify($cur,$cfg_get_hash())){ echo json_encode(['ok'=>false,'msg'=>'বর্তমান password ভুল।']); exit(); }
            if(strlen($new)<8){ echo json_encode(['ok'=>false,'msg'=>'নতুন password কমপক্ষে ৮ অক্ষরের হতে হবে।']); exit(); }
            if($new!==$cnf){ echo json_encode(['ok'=>false,'msg'=>'নতুন password ও confirm মিলছে না।']); exit(); }
            $h=password_hash($new,PASSWORD_BCRYPT,['cost'=>12]);
            $he=mysqli_real_escape_string($conn,$h);
            $conn->query("INSERT INTO admin_settings (setting_key,setting_value) VALUES ('cfg_editor_hash','$he') ON DUPLICATE KEY UPDATE setting_value='$he'");
            auditLog($conn,'CFG_CHANGE_PASS','config editor password changed');
            echo json_encode(['ok'=>true,'msg'=>'✅ Config password পরিবর্তন হয়েছে।']); exit();
        }
    }

    // ── SMS Gateway — load env.sms ────────────────────────
    if($act==='sms_get'){
        $path=__DIR__.'/.env.sms';
        $cfg=[];
        if(is_file($path)){
            $lines=file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
            foreach($lines as $line){
                if(substr(trim($line),0,1)==='#') continue;
                if(strpos($line,'=')===false) continue;
                [$k,$v]=explode('=',$line,2);
                $cfg[trim($k)]=trim($v);
            }
        }
        echo json_encode(['ok'=>true,'values'=>$cfg]); exit();
    }

    // ── SMS Gateway — save env.sms ────────────────────────
    if($act==='sms_save'){
        guardSuper('SMS Gateway');
        $payload=$_POST['sms']??[];
        if(is_string($payload)) $payload=json_decode($payload,true);
        if(!is_array($payload)){
            echo json_encode(['ok'=>false,'msg'=>'Invalid data.']); exit();
        }
        $allowedKeys=[
            'SMS_ENDPOINT','SMS_SERVER_ADDRESS','SMS_USERNAME','SMS_PASSWORD',
            'SMS_DEVICE_ID','SMS_ENABLED','SMS_AUTH_TYPE'
        ];
        $lines=[];
        $lines[]='# SMS Gateway Config';
        foreach($allowedKeys as $key){
            $v=trim((string)($payload[$key]??''));
            if($v!=='') $lines[]="{$key}={$v}";
        }
        $lines[]='';
        $lines[]='# basic = SMSGate style (Basic Auth + JSON)';
        $lines[]='# bearer = Bearer token style';
        $lines[]='# custom = custom header';
        $content=implode("\n",$lines);
        $path=__DIR__.'/.env.sms';
        $tmp=$path.'.tmp';
        if(@file_put_contents($tmp,$content)===false || !@rename($tmp,$path)){
            @unlink($tmp);
            echo json_encode(['ok'=>false,'msg'=>'❌ env.sms লিখতে পারছি না — permission চেক করুন।']); exit();
        }
        auditLog($conn,'SMS_SAVE','SMS gateway config updated');
        echo json_encode(['ok'=>true,'msg'=>'✅ SMS Gateway config সংরক্ষিত!']); exit();
    }

    // ── SMS Gateway — test send ───────────────────────────
    if($act==='sms_test'){
        guardSuper('SMS Gateway');
        require_once __DIR__.'/includes/SmsGateway.php';
        $gateway=new SmsGateway();
        if(!$gateway->isConfigured()){
            echo json_encode(['ok'=>false,'msg'=>'❌ SMS gateway configured না।']);
            exit();
        }
        $phone=trim($_POST['test_phone']??'');
        if(empty($phone)) $phone=defined('CONTACT_PHONE')?CONTACT_PHONE:'';
        if(empty($phone)){
            echo json_encode(['ok'=>false,'msg'=>'❌ Test SMS এর জন্য phone number দিন।']); exit();
        }
        $testMsg="🩸 Blood Arena — SMS Gateway test message.\n\nIf you receive this, your SMS config is working correctly.";
        $result=$gateway->send($phone,$testMsg);
        $errMsg=$result['error']??'unknown';
        if($result['success']){
            echo json_encode(['ok'=>true,'msg'=>"✅ Test SMS পাঠানো হয়েছে {$phone} নম্বরে।"]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>"❌ Test ব্যর্থ: {$errMsg}"]);
        }
        exit();
    }

    // ── Send Bulk Notification ───────────────────────────
    if($act==='send_notif_bulk' && $conn){
        $type=trim($_POST['notif_type']??'info');
        $message=trim($_POST['message']??'');
        $vtypes=['location_on','notif_on','info','warning'];
        if(!in_array($type,$vtypes,true)) $type='info';
        if(empty($message)){echo json_encode(['ok'=>false,'msg'=>'Message লিখুন।']);exit();}
        if(mb_strlen($message,'UTF-8')>500){echo json_encode(['ok'=>false,'msg'=>'সর্বোচ্চ ৫০০ অক্ষর।']);exit();}
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @$conn->query("CREATE TABLE IF NOT EXISTS `push_subscriptions`(`id` INT AUTO_INCREMENT PRIMARY KEY,`endpoint` TEXT NOT NULL,`p256dh` TEXT NOT NULL,`auth` TEXT NOT NULL,`device_id` VARCHAR(100) DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $allDevices=[];
        $rps=$conn->query("SELECT DISTINCT device_id FROM push_subscriptions WHERE device_id IS NOT NULL AND device_id!=''");
        if($rps) while($r=$rps->fetch_assoc()) $allDevices[$r['device_id']]=true;
        $rsn=$conn->query("SELECT DISTINCT device_id FROM service_notifications WHERE device_id!=''");
        if($rsn) while($r=$rsn->fetch_assoc()) $allDevices[$r['device_id']]=true;
        // Collect device_ids from donors table (most reliable source)
        $rdn=$conn->query("SELECT DISTINCT device_id FROM donors WHERE device_id IS NOT NULL AND device_id!=''");
        if($rdn) while($r=$rdn->fetch_assoc()) $allDevices[$r['device_id']]=true;
        // Also include devices from admin_messages
        @$conn->query("CREATE TABLE IF NOT EXISTS `admin_messages`(`id` INT AUTO_INCREMENT PRIMARY KEY,`sender_name` VARCHAR(100),`sender_phone` VARCHAR(20),`message` TEXT,`device_id` VARCHAR(100),`is_read` TINYINT DEFAULT 0,`admin_reply` TEXT DEFAULT NULL,`replied_at` TIMESTAMP NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ramsg=$conn->query("SELECT DISTINCT device_id FROM admin_messages WHERE device_id IS NOT NULL AND device_id!=''");
        if($ramsg) while($r=$ramsg->fetch_assoc()) $allDevices[$r['device_id']]=true;
        $count=0;
        foreach(array_keys($allDevices) as $did){
            $sn=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
            $sn->bind_param("sss",$did,$type,$message);$sn->execute();$sn->close();
            $count++;
        }
        auditLog($conn,'BULK_NOTIF',"type:$type devices:$count msg:".mb_substr($message,0,60));
        echo json_encode(['ok'=>true,'msg'=>"✅ {$count} টি device এ notification পাঠানো হয়েছে।",'count'=>$count]); exit();
    }

    // ── Send Notification to Specific Donor ─────────────
    if($act==='send_notif_donor' && $conn){
        $phone=trim($_POST['donor_phone']??'');
        $type=trim($_POST['notif_type']??'info');
        $message=trim($_POST['message']??'');
        $vtypes=['location_on','notif_on','info','warning'];
        if(!in_array($type,$vtypes,true)) $type='info';
        if(empty($phone)||empty($message)){echo json_encode(['ok'=>false,'msg'=>'Phone ও message দিন।']);exit();}
        if(!preg_match('/^\+8801\d{9}$/',$phone)){echo json_encode(['ok'=>false,'msg'=>'সঠিক ফোন নম্বর দিন।']);exit();}
        if(mb_strlen($message,'UTF-8')>500){echo json_encode(['ok'=>false,'msg'=>'সর্বোচ্চ ৫০০ অক্ষর।']);exit();}
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // 1st: donors.device_id (most reliable — stored at registration/login)
        $did='';
        $dq1=$conn->prepare("SELECT device_id FROM donors WHERE phone=? AND device_id IS NOT NULL AND device_id!='' LIMIT 1");
        $dq1->bind_param("s",$phone);$dq1->execute();
        $drow1=$dq1->get_result()->fetch_assoc();$dq1->close();
        if(!empty($drow1['device_id'])) $did=$drow1['device_id'];
        if(empty($did)){echo json_encode(['ok'=>false,'msg'=>'❌ এই donor এর device ID পাওয়া যায়নি। Donor কে app থেকে একবার info update করতে বলুন।']);exit();}
        $sn=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
        $sn->bind_param("sss",$did,$type,$message);$sn->execute();$sn->close();
        auditLog($conn,'DONOR_NOTIF',"phone:$phone type:$type");
        echo json_encode(['ok'=>true,'msg'=>"✅ Notification পাঠানো হয়েছে।"]); exit();
    }

    // ── Send Notification to Multiple Selected Donors ──────
    if($act==='send_notif_selected_donors' && $conn){
        $ids_raw=trim($_POST['donor_ids']??'');
        $type=trim($_POST['notif_type']??'info');
        $message=trim($_POST['message']??'');
        $vtypes=['location_on','notif_on','info','warning'];
        if(!in_array($type,$vtypes,true)) $type='info';
        if(empty($ids_raw)||empty($message)){echo json_encode(['ok'=>false,'msg'=>'IDs ও message দিন।']);exit();}
        if(mb_strlen($message,'UTF-8')>500){echo json_encode(['ok'=>false,'msg'=>'সর্বোচ্চ ৫০০ অক্ষর।']);exit();}
        $ids=array_filter(array_map('intval',explode(',',$ids_raw)));
        if(empty($ids)){echo json_encode(['ok'=>false,'msg'=>'কোনো donor select করা হয়নি।']);exit();}
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Find device_ids for these donor IDs via phone number matching
        $placeholders=implode(',',array_fill(0,count($ids),'?'));
        $phones_q=$conn->prepare("SELECT phone FROM donors WHERE id IN ($placeholders)");
        $phones_q->bind_param(str_repeat('i',count($ids)),...$ids);
        $phones_q->execute();
        $phones_res=$phones_q->get_result();
        $phones=[];
        while($pr=$phones_res->fetch_assoc()) $phones[]=$pr['phone'];
        $phones_q->close();
        $sent=0; $no_device=0;
        // Also get device_id directly from donors table
        $donor_device_map = [];
        $ph_list = implode(',', array_fill(0, count($phones), '?'));
        if(!empty($phones)){
            $ddq = $conn->prepare("SELECT phone, device_id FROM donors WHERE phone IN ($ph_list) AND device_id IS NOT NULL AND device_id!=''");
            $ddq->bind_param(str_repeat('s',count($phones)), ...$phones);
            $ddq->execute();
            $ddr = $ddq->get_result();
            while($ddr_row=$ddr->fetch_assoc()) $donor_device_map[$ddr_row['phone']] = $ddr_row['device_id'];
            $ddq->close();
        }
        foreach($phones as $phone){
            // 1st: donors.device_id (most reliable)
            $did = $donor_device_map[$phone] ?? '';
            if(empty($did)){ $no_device++; continue; }
            $sn=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
            $sn->bind_param("sss",$did,$type,$message); $sn->execute(); $sn->close();
            $sent++;
        }
        auditLog($conn,'SELECTED_NOTIF',"donors:".count($ids)." sent:$sent no_device:$no_device type:$type");
        $msg="✅ $sent জন donor কে notification পাঠানো হয়েছে।";
        if($no_device>0) $msg.=" ($no_device জনের device ID নেই)";
        echo json_encode(['ok'=>true,'msg'=>$msg,'sent'=>$sent,'no_device'=>$no_device]); exit();
    }

    // ── Admin FCM Push — সরাসরি phone notification panel এ যাবে ──
    if($act==='send_fcm_push' && $conn){
        $push_title  = trim($_POST['push_title']  ?? '');
        $push_body   = trim($_POST['push_body']   ?? '');
        $target      = trim($_POST['push_target'] ?? 'broadcast');
        $donor_ids_r = trim($_POST['donor_ids']   ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        if(empty($push_title)||empty($push_body)){echo json_encode(['ok'=>false,'msg'=>'Title ও Body দিন।']);exit();}
        if(mb_strlen($push_title,'UTF-8')>100) $push_title=mb_substr($push_title,0,100,'UTF-8');
        if(mb_strlen($push_body,'UTF-8')>400)  $push_body=mb_substr($push_body,0,400,'UTF-8');
        // Salaam prefix যদি না থাকে
        if(mb_strpos($push_body,"\xe0\xa6\x86\xe0\xa6\xb8\xe0\xa6\xb8",0,'UTF-8')===false)
            $push_body = "\xE0\xA6\x86\xE0\xA6\xB8\xE0\xA6\xB8\xE0\xA6\xBE\xE0\xA6\xB2\xE0\xA6\xBE\xE0\xA6\xAE\xE0\xA7\x81 \xE0\xA6\x86\xE0\xA6\xB2\xE0\xA6\xBE\xE0\xA6\x87\xE0\xA6\x95\xE0\xA7\x81\xE0\xA6\xAE! \xF0\x9F\x8C\x99\n\n".$push_body;
        // FCM auth
        function _adm_fcm_base64url($d){return rtrim(strtr(base64_encode($d),'+/','-_'),'=');}
        function _adm_get_oauth(){
            $cf=__DIR__.'/.fcm_token_cache';
            if(file_exists($cf)){$c=@json_decode(@file_get_contents($cf),true);if($c&&isset($c['token'],$c['exp'])&&time()<$c['exp'])return $c['token'];}
            $ce="firebase-adminsdk-fbsvc@shsmc-blood-portal.iam.gserviceaccount.com";
            $pk="-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCaYTTALQ5tu9/j\n2IviUJI5F6nMwLYIGdAJKIMVdml4gxrgptWEJYXTb7e5p5yFlu9sGpDdcRD+BUlB\nXy8TnRtieQ1B1Kjqko+EyXVsIC+Kf+CN/yq/mVCySFEBgzOe+2efxWSvZiLQdv/6\nV7PfVJS9Mv0/hFtnUC+6EKKRBPDsLo8d4qD8hftdNBL5lWS4XtBP7MjEKLm6S3QO\nMlbgxTeLFspcb7eNZK755c4C3AHyhdnrBNNmHZTlVuVGVtfK5UXq3MVKyW10/Ek2\naurh+kfzow3OgEk6SW46dL31KkGYa2GFxOS/6rlaGMiFK0QkJL1GNa2BCFAQYT7J\nAPAzvJPjAgMBAAECggEAC2xTQZUT6D1qnQASTwtfMSegbNd69gZ9mkU2eIlr1yWn\nANCCJBESt0kg8x/ajm1TXKW/6mLKEGxxCzab09EK4bJ2BKpTsBJq2Yx+n82R4acC\nBVUdjf0uN22acN41x6HFUnvXWL3Z/aA7OK7x+aiB3li+McuoEnD11x1mqgxk4f+X\n2/Iie+fYBnL/OQoHMi7w/XHnHqoqGiWQLP/mTfzX43albR2b/JR0cVHii//hqeMz\nmlF3rv6fTIfh+mBxBH2GtjN93LaNsBWpitMER2hpX7gG/INEy7sUXI6jz2Rh7/Lf\nNWWzKU//xu37j7GtsV+LLak04TZ4ByfaFA4r7VdIYQKBgQDMCbhenujXz6JM0AHR\nde4wAp4xJrxB/wa4EXUJXzEFfPe5rlWjii7dXUWo2oYp6j1sHcBH3GbfhGlrp9oB\nvi+Kb3DwWBhGB6MCE4YJdaIEpIdWEoxEXirzQSf0yE1OKYjEkavENEuoOQssL2lA\nqv0fLsIWWKL6ouQXh1ozuZDBDQKBgQDBsgKAsRyLUvz05d4se3teZhJPgguk52tT\nL3wpFiRSIsB8zuIP0IH+ovp1puerdDZCtvf/lTS431EU/Hfe3orEgZHEd2vWD3xi\nxeHmtw9e0t7UkIu0q7LsUTJM+XhL9p7NydFNXTW1nH2bVNkHCu7JGvNZyvST2KAS\nJSGQTKwMrwKBgDJIhvZSpUFiOzZA4OHU9WFBk+i7ChQdnHNKYhRwMC2REZ/h9dr6\n1/fX363wRLYZsw9s+ZD8ISIeiLhuQkzBqQet1SB2JW1EvohpdVPpeIc6YNv2cDj9\nGAqg2Q77Ogn0NG91EuakmKyZekZmXMMCIKVJqa1GJMwtzpZ51eH/bkwVAoGBAIow\nGaD+usKbfmSp6owJvMZoQ//9Y5lOkT9TzVzysw72RCXG43ks5NFqLQ3q+bVUv7Fx\nIBVzuZ17lTlHta2HT7FKT1i/amvZuIAvdS9Iwup/vwIf7cwEAy6d7ykDglOPq1Rd\n+7kaGstqziIXso5Xumw3kg4pwbwI/Ip1ezCbwtN5AoGALxmWPYZ4bLwd5CkUop+S\nAjk1S4U2XNxVO+WXeEGc1ZyqyV6sjzf/cU0FkXZ8F1UA2WcEj54/9O0bp7d1Asss\nZN4YF8seZuzPSce2KMXdCJK8U6B7yg60CINZ2YxyCbxAJxbyPOt03+/WxTtuNoaR\n7WPRHDoF1VMk/DzBD4d6yP0=\n-----END PRIVATE KEY-----\n";
            $now=time();
            $h=_adm_fcm_base64url(json_encode(['alg'=>'RS256','typ'=>'JWT']));
            $p=_adm_fcm_base64url(json_encode(['iss'=>$ce,'scope'=>'https://www.googleapis.com/auth/firebase.messaging','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600]));
            $si=$h.'.'.$p;$sig='';$pk2=openssl_pkey_get_private($pk);if(!$pk2)return null;
            openssl_sign($si,$sig,$pk2,'SHA256');
            $jwt=$si.'.'._adm_fcm_base64url($sig);
            $ch=curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);
            $res=curl_exec($ch);curl_close($ch);if(!$res)return null;
            $data=json_decode($res,true);if(empty($data['access_token']))return null;
            @file_put_contents($cf,json_encode(['token'=>$data['access_token'],'exp'=>$now+3300]));
            return $data['access_token'];
        }
        // Collect tokens by target
        $where_tok='1=1';
        if($target==='active_donors'){
            $where_tok="device_id IN (SELECT device_id FROM donors WHERE willing_to_donate='yes' AND device_id IS NOT NULL AND device_id!='')";
        } elseif($target==='donor_ids' && !empty($donor_ids_r)){
            $ids=array_filter(array_map('intval',explode(',',$donor_ids_r)));
            if(!empty($ids)){
                $id_csv=implode(',',$ids);
                $pr=$conn->query("SELECT phone FROM donors WHERE id IN ($id_csv)");
                $phs=[];if($pr)while($row=$pr->fetch_assoc())$phs[]="'".mysqli_real_escape_string($conn,$row['phone'])."'";;
                if(!empty($phs))$where_tok="device_id IN (SELECT device_id FROM donors WHERE phone IN (".implode(',',$phs).") AND device_id IS NOT NULL AND device_id!='')";
            }
        } elseif($target==='blood_group' && !empty($blood_group)){
            $ebg=mysqli_real_escape_string($conn,$blood_group);
            $where_tok="device_id IN (SELECT device_id FROM donors WHERE blood_group='$ebg' AND device_id IS NOT NULL AND device_id!='')";
        }
        $tq=$conn->query("SELECT fcm_token FROM fcm_tokens WHERE $where_tok");
        $tokens=[];if($tq)while($tr=$tq->fetch_assoc())$tokens[]=$tr['fcm_token'];
        if(empty($tokens)){echo json_encode(['ok'=>false,'msg'=>'❌ কোনো FCM token নেই। Subscriber নেই।']);exit();}
        $oauth=@_adm_get_oauth();
        if(!$oauth){echo json_encode(['ok'=>false,'msg'=>'❌ FCM auth token পাওয়া যায়নি।']);exit();}
        $ep='https://fcm.googleapis.com/v1/projects/shsmc-blood-portal/messages:send';
        $pid='adm_'.time();$sent=0;$fail=0;$stale_tokens=[];
        $auth_hdr=['Content-Type: application/json','Authorization: Bearer '.$oauth];

        // ── PARALLEL push — curl_multi দিয়ে সবাইকে একসাথে ──
        $mh      = curl_multi_init();
        $handles = [];
        $tok_map = [];
        foreach($tokens as $tok){
            // Data-only — notification field থাকলে onBackgroundMessage bypass হয়,
            // ফলে SW notification দেখায় না (push আসে না)। তাই title/body data-তে পাঠাই।
            $pl=json_encode(['message'=>[
                'token'=>$tok,
                'webpush'=>[
                    'fcm_options'=>['link'=>SITE_URL.'/']
                ],
                'data'=>[
                    'type'    =>'admin_push',
                    'push_id' =>$pid,
                    'title'   =>$push_title,
                    'body'    =>$push_body,
                    'message' =>$push_body,
                    'url'     =>SITE_URL.'/'
                ]
            ]]);
            $ch=curl_init($ep);
            curl_setopt_array($ch,[
                CURLOPT_POST=>true,
                CURLOPT_HTTPHEADER=>$auth_hdr,
                CURLOPT_POSTFIELDS=>$pl,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_TIMEOUT=>8,
                CURLOPT_CONNECTTIMEOUT=>4,
            ]);
            curl_multi_add_handle($mh,$ch);
            $tok_map[(int)$ch]=$tok;
            $handles[]=$ch;
        }
        // ── Run all in parallel ──
        $running=null;
        do{
            curl_multi_exec($mh,$running);
            if($running) curl_multi_select($mh,0.5);
        }while($running>0);

        // ── Collect results ──
        foreach($handles as $ch){
            $r=curl_multi_getcontent($ch);
            $info=curl_getinfo($ch);
            if($r && $info['http_code']===200){
                $sent++;
            } else {
                $fail++;
                if($r){
                    $fcm_r=json_decode($r,true);
                    if(!empty($fcm_r['error']['status']) &&
                       in_array($fcm_r['error']['status'],['UNREGISTERED','INVALID_ARGUMENT'],true)){
                        $stale_tokens[]=$tok_map[(int)$ch];
                    }
                }
            }
            curl_multi_remove_handle($mh,$ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // ── Stale token DB থেকে মুছো ──
        $cleaned=0;
        if(!empty($stale_tokens)){
            mysqli_report(MYSQLI_REPORT_OFF);
            foreach($stale_tokens as $st){
                $del=$conn->prepare("DELETE FROM fcm_tokens WHERE fcm_token=?");
                $del->bind_param("s",$st); $del->execute(); $del->close();
            }
            $cleaned=count($stale_tokens);
            mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        }
        // Also store in service_notifications for in-app bell
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $dq=$conn->query("SELECT DISTINCT device_id FROM fcm_tokens WHERE device_id IS NOT NULL AND device_id!=''");
        if($dq){while($dr=$dq->fetch_assoc()){
            $sn=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
            $sn->bind_param("sss",$dr['device_id'],'info',$push_body);$sn->execute();$sn->close();
        }}
        auditLog($conn,'ADMIN_FCM_PUSH',"target:$target sent:$sent fail:$fail cleaned:$cleaned");
        $result_msg = "✅ $sent জন subscriber এর phone-এ notification পাঠানো হয়েছে।";
        if($fail>0) $result_msg .= " ($fail failed";
        if($cleaned>0) $result_msg .= ", $cleaned টি expired token auto-cleaned";
        if($fail>0) $result_msg .= ")";
        echo json_encode(['ok'=>true,'msg'=>$result_msg,'sent'=>$sent,'fail'=>$fail,'cleaned'=>$cleaned]); exit();
    }

    // ── Auto Reminder for Not-Willing Donors (triggers every 3 days) ─
    if($act==='run_auto_reminder' && $conn){
        mysqli_report(MYSQLI_REPORT_OFF);
        // ── Ensure all required tables and columns exist ──
        $conn->query("CREATE TABLE IF NOT EXISTS `donor_reminder_log`(
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donor_id` INT NOT NULL,
            `donor_phone` VARCHAR(20) NOT NULL,
            `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(donor_id), INDEX(sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Ensure device_id column exists on donors (older installs may not have it)
        $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS device_id VARCHAR(100) DEFAULT NULL");
        // Ensure device_tokens table exists (3rd fallback source)
        $conn->query("CREATE TABLE IF NOT EXISTS `device_tokens`(
            `device_id` VARCHAR(100) PRIMARY KEY,
            `context` VARCHAR(30) DEFAULT 'unknown',
            `ip` VARCHAR(50) DEFAULT NULL,
            `ua` VARCHAR(300) DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @$conn->query("CREATE TABLE IF NOT EXISTS `admin_messages`(`id` INT AUTO_INCREMENT PRIMARY KEY,`sender_name` VARCHAR(100),`sender_phone` VARCHAR(20),`message` TEXT,`device_id` VARCHAR(100),`is_read` TINYINT DEFAULT 0,`admin_reply` TEXT DEFAULT NULL,`replied_at` TIMESTAMP NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Rate-limit: once per hour unless forced
        $conn->query("INSERT IGNORE INTO admin_settings (setting_key,setting_value) VALUES ('last_auto_reminder_run','0')");
        $last_run_r=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='last_auto_reminder_run' LIMIT 1");
        $last_run=(int)($last_run_r?$last_run_r->fetch_assoc()['setting_value']:0);
        $force = trim($_POST['force']??'') === '1';
        if(!$force && (time()-$last_run)<3600){
            echo json_encode(['ok'=>true,'skipped'=>true,'msg'=>'⏳ শেষ Reminder-এর পর ১ ঘণ্টা হয়নি। Force করতে আবার চাপুন।','last_run'=>date('d M H:i',$last_run)]);
            exit();
        }
        $conn->query("UPDATE admin_settings SET setting_value='".time()."' WHERE setting_key='last_auto_reminder_run'");

        // ── Find not-willing donors due for reminder ──
        $remind_q=$conn->query("SELECT d.id, d.phone, d.name, d.device_id FROM donors d
            WHERE d.willing_to_donate='no'
            AND (
                NOT EXISTS (SELECT 1 FROM donor_reminder_log l WHERE l.donor_id=d.id AND l.sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY))
            )
            LIMIT 50");
        if(!$remind_q){
            echo json_encode(['ok'=>false,'msg'=>'❌ Query failed: '.($conn->error??'unknown error')]);
            exit();
        }

        $sent=0; $no_device=0;
        while($donor=$remind_q->fetch_assoc()){
            // 1st priority: donors.device_id (set at registration/update)
            $did = trim($donor['device_id'] ?? '');


            // 3rd fallback: admin_messages (donor sent a message to admin)
            if(empty($did)){
                $dq2=$conn->prepare("SELECT device_id FROM admin_messages WHERE sender_phone=? AND device_id IS NOT NULL AND device_id!='' ORDER BY id DESC LIMIT 1");
                $dq2->bind_param("s",$donor['phone']); $dq2->execute();
                $drow2=$dq2->get_result()->fetch_assoc(); $dq2->close();
                $did=trim($drow2['device_id'] ?? '');
            }

            if(empty($did)){ $no_device++; continue; }

            $rmsg="আসসালামু আলাইকুম, প্রিয় ".$donor['name']." ভাই/আপু! 🌙\n\n"
                ."🩸 আপনি এখনো রক্তদানে অনিচ্ছুক হিসেবে চিহ্নিত আছেন।\n\n"
                ."হয়তো এতদিনে আপনি সুস্থ আছেন এবং আবার রক্ত দিতে প্রস্তুত!\n"
                ."অনেক রোগী আপনার মতো মানুষের রক্তের অপেক্ষায় আছেন। 🙏\n\n"
                ."💡 Available হতে:\nRegister → Update My Info → 'রক্তদানে ইচ্ছুক' চালু করুন।\n\n"
                ."আল্লাহ আপনার এই মহৎ কাজে উত্তম প্রতিদান দিন। ❤️";
            $sn=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
            $sn->bind_param("sss",$did,'info',$rmsg); $sn->execute(); $sn->close();
            $log=$conn->prepare("INSERT INTO donor_reminder_log (donor_id,donor_phone) VALUES (?,?)");
            $log->bind_param("is",$donor['id'],$donor['phone']); $log->execute(); $log->close();
            $sent++;
        }

        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        auditLog($conn,'AUTO_REMINDER',"sent:$sent no_device:$no_device");

        $msg = "✅ $sent জন not-willing donor কে reminder পাঠানো হয়েছে।";
        if($no_device>0) $msg .= " ($no_device জনের device ID নেই — তারা কখনো app login করেননি)";
        if($sent===0 && $no_device===0) $msg = "ℹ️ কোনো not-willing donor নেই, বা সবাইকে গত ৩ দিনের মধ্যে reminder পাঠানো হয়েছে।";
        echo json_encode(['ok'=>true,'sent'=>$sent,'no_device'=>$no_device,'msg'=>$msg]); exit();
    }



    // ── Admin Inbox: Get Messages ────────────────────────
    if($act==='get_inbox' && $conn){
        ensureAdminTables($conn);
        $filter=trim($_POST['filter']??'all'); // all | unread | replied
        $where='1';
        if($filter==='unread') $where='is_read=0 AND admin_reply IS NULL';
        elseif($filter==='replied') $where='admin_reply IS NOT NULL';
        $res=$conn->query("SELECT id,sender_name,sender_phone,message,device_id,is_read,admin_reply,replied_at,created_at FROM admin_messages WHERE $where ORDER BY created_at DESC LIMIT 100");
        $rows=[];
        if($res) while($r=$res->fetch_assoc()) $rows[]=$r;
        $unread=(int)_cnt($conn->query("SELECT COUNT(*) c FROM admin_messages WHERE is_read=0 AND admin_reply IS NULL"));
        echo json_encode(['ok'=>true,'rows'=>$rows,'unread'=>$unread]); exit();
    }

    // ── Admin Inbox: Reply to Message ────────────────────
    if($act==='reply_inbox_msg' && $conn && $id>0){
        $reply=trim($_POST['reply']??'');
        if(empty($reply)){ echo json_encode(['ok'=>false,'msg'=>'Reply লিখুন।']); exit(); }
        if(mb_strlen($reply,'UTF-8')>1000){ echo json_encode(['ok'=>false,'msg'=>'Reply সর্বোচ্চ ১০০০ অক্ষর।']); exit(); }
        // Get device_id for service notification
        $mq=$conn->prepare("SELECT device_id,sender_name FROM admin_messages WHERE id=?");
        $mq->bind_param("i",$id); $mq->execute();
        $mrow=$mq->get_result()->fetch_assoc(); $mq->close();
        if(!$mrow){ echo json_encode(['ok'=>false,'msg'=>'Message পাওয়া যায়নি।']); exit(); }
        // Save reply
        $upd=$conn->prepare("UPDATE admin_messages SET admin_reply=?,replied_at=NOW(),is_read=1 WHERE id=?");
        $upd->bind_param("si",$reply,$id); $upd->execute(); $upd->close();
        // Send service notification to device
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $notifMsg="আসসালামু আলাইকুম! 🌙\n\n💬 Admin Reply:\n".$reply."\n\nআপনার বার্তার জবাব দেওয়া হয়েছে। আরও কিছু জানার থাকলে যোগাযোগ করুন। 🤝";
        $ntype='info';
        $did=$mrow['device_id'];
        $ns=$conn->prepare("INSERT INTO service_notifications (device_id,type,message) VALUES (?,?,?)");
        $ns->bind_param("sss",$did,$ntype,$notifMsg); $ns->execute(); $ns->close();
        auditLog($conn,'INBOX_REPLY',"id:$id device:".substr($did,0,20));
        echo json_encode(['ok'=>true,'msg'=>'✅ Reply পাঠানো হয়েছে এবং user notification পেবে।']); exit();
    }

    // ── Admin Inbox: Mark as Read ────────────────────────
    if($act==='mark_inbox_read' && $conn && $id>0){
        $conn->query("UPDATE admin_messages SET is_read=1 WHERE id=$id");
        echo json_encode(['ok'=>true]); exit();
    }

    // ── Delete Single Inbox Message ───────────────────────
    if($act==='del_inbox_msg' && $conn && $id>0){
        $stmt=$conn->prepare("DELETE FROM admin_messages WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $aff=$stmt->affected_rows; $stmt->close();
        auditLog($conn,'DEL_INBOX_MSG',"id:$id");
        echo json_encode(['ok'=>$aff>0]); exit();
    }

    // ── Clear All Inbox Messages ──────────────────────────
    if($act==='clear_inbox' && $conn){
        $filter=trim($_POST['filter']??'all');
        if($filter==='all'){
            $conn->query("DELETE FROM admin_messages");
        } elseif($filter==='replied'){
            $conn->query("DELETE FROM admin_messages WHERE admin_reply IS NOT NULL");
        } elseif($filter==='unread'){
            $conn->query("DELETE FROM admin_messages WHERE is_read=0 AND admin_reply IS NULL");
        }
        $aff=(int)$conn->affected_rows;
        auditLog($conn,'CLEAR_INBOX',"filter:$filter deleted:$aff");
        echo json_encode(['ok'=>true,'deleted'=>$aff,'msg'=>"✅ $aff টি message মুছে ফেলা হয়েছে।"]); exit();
    }

    // ── Self-Reported (off-platform) Donations: list for audit ───
    //  source='self' rows = ব্যবহারকারীর নিজে যোগ করা off-platform রক্তদান।
    //  schema v16-এর column গুলো না থাকলে (পুরোনো DB) খালি list ফেরাই।
    if($act==='get_self_reports' && $conn){
        $rows=[];
        try {
            $res=$conn->query("SELECT dh.id, dh.donation_date, dh.note, dh.reported_ip,
                    UNIX_TIMESTAMP(dh.created_at) AS ts, dh.donor_id,
                    d.name, d.phone, d.total_donations
                FROM donation_history dh LEFT JOIN donors d ON d.id=dh.donor_id
                WHERE dh.source='self' ORDER BY dh.created_at DESC, dh.id DESC LIMIT 100");
            if($res) while($r=$res->fetch_assoc()) $rows[]=$r;
        } catch (\Throwable $e) { $rows=[]; }
        echo json_encode(['ok'=>true,'rows'=>$rows]); exit();
    }

    // ── Revoke a self-reported donation (super admin only) ───────
    //  history row delete + donor count −1 + badge পুনরায় হিসাব + analytics −1।
    if($act==='revoke_self_report' && $conn && $id>0){
        $did=0;
        try {
            $q=$conn->prepare("SELECT donor_id FROM donation_history WHERE id=? AND source='self'");
            $q->bind_param("i",$id); $q->execute();
            $hr=$q->get_result()->fetch_assoc(); $q->close();
            if(!$hr){ echo json_encode(['ok'=>false,'msg'=>'Self-reported entry পাওয়া যায়নি।']); exit(); }
            $did=(int)$hr['donor_id'];
            $del=$conn->prepare("DELETE FROM donation_history WHERE id=?");
            $del->bind_param("i",$id); $del->execute(); $del->close();
            if($did>0){
                $conn->query("UPDATE donors SET total_donations=GREATEST(0,total_donations-1) WHERE id=$did");
                $conn->query("UPDATE donors SET badge_level=CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END WHERE id=$did");
            }
            $conn->query("UPDATE analytics_counters SET counter_value=GREATEST(0,counter_value-1) WHERE counter_name='total_donations_ever'");
        } catch (\Throwable $e) { echo json_encode(['ok'=>false,'msg'=>'DB error।']); exit(); }
        auditLog($conn,'REVOKE_SELF_REPORT',"hist:$id donor:$did");
        echo json_encode(['ok'=>true,'msg'=>'✅ Self-reported রক্তদান বাতিল — donation count −১ করা হয়েছে।']); exit();
    }

    // ── Moderator Management ─────────────────────────────
    if($act==='list_moderators' && $conn){
        ensureAdminTables($conn);
        $res=$conn->query("SELECT id,username,role,is_active,created_at FROM admin_users ORDER BY id DESC");
        $rows=[];
        if($res) while($r=$res->fetch_assoc()) $rows[]=$r;
        echo json_encode(['ok'=>true,'rows'=>$rows]); exit();
    }

    if($act==='add_moderator' && $conn){
        $uname=trim($_POST['uname']??'');
        $pass =trim($_POST['pass'] ??'');
        $role =trim($_POST['role'] ??'moderator');
        if(!in_array($role,['super_admin','moderator'],true)) $role='moderator';
        if(empty($uname)||empty($pass)){ echo json_encode(['ok'=>false,'msg'=>'Username ও Password দিন।']); exit(); }
        if(mb_strlen($uname,'UTF-8')>60||!preg_match('/^[a-zA-Z0-9_\-]+$/',$uname)){ echo json_encode(['ok'=>false,'msg'=>'Username শুধু a-z 0-9 _ - ব্যবহার করুন (max 60)।']); exit(); }
        if(strlen($pass)<8){ echo json_encode(['ok'=>false,'msg'=>'Password কমপক্ষে ৮ অক্ষর।']); exit(); }
        // Check duplicate
        $chk=$conn->prepare("SELECT id FROM admin_users WHERE username=?");
        $chk->bind_param("s",$uname); $chk->execute();
        if($chk->get_result()->num_rows>0){ $chk->close(); echo json_encode(['ok'=>false,'msg'=>'এই username ইতোমধ্যে আছে।']); exit(); }
        $chk->close();
        $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
        $ins=$conn->prepare("INSERT INTO admin_users (username,pass_hash,role,is_active) VALUES (?,?,?,1)");
        $ins->bind_param("sss",$uname,$hash,$role); $ins->execute();
        $newId=(int)$conn->insert_id; $ins->close();
        auditLog($conn,'ADD_MODERATOR',"uname:$uname role:$role");
        echo json_encode(['ok'=>true,'id'=>$newId,'username'=>$uname,'role'=>$role,'is_active'=>1,'created_at'=>date('d M Y')]); exit();
    }

    if($act==='del_moderator' && $conn && $id>0){
        $stmt=$conn->prepare("DELETE FROM admin_users WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $aff=$stmt->affected_rows; $stmt->close();
        if($aff>0){ auditLog($conn,'DEL_MODERATOR',"id:$id"); echo json_encode(['ok'=>true]); }
        else { echo json_encode(['ok'=>false,'msg'=>'User পাওয়া যায়নি।']); }
        exit();
    }

    // ── Admin Poll — returns counts for notification polling ──
    if($act==='get_admin_poll' && $conn){
        ensureAdminTables($conn);
        @$conn->query("CREATE TABLE IF NOT EXISTS `admin_messages`(`id` INT AUTO_INCREMENT PRIMARY KEY,`sender_name` VARCHAR(100),`sender_phone` VARCHAR(20),`message` TEXT,`device_id` VARCHAR(100),`is_read` TINYINT DEFAULT 0,`admin_reply` TEXT DEFAULT NULL,`replied_at` TIMESTAMP NULL DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $since = (int)($_POST['since'] ?? 0); // Unix timestamp of last known item
        // New inbox messages since last poll
        $inbox_q = $conn->query("SELECT id,sender_name,sender_phone,message,UNIX_TIMESTAMP(created_at) as ts FROM admin_messages WHERE is_read=0 AND admin_reply IS NULL".($since?" AND UNIX_TIMESTAMP(created_at)>$since":"")." ORDER BY id DESC LIMIT 10");
        $new_inbox = [];
        if($inbox_q) while($r=$inbox_q->fetch_assoc()) $new_inbox[] = $r;
        // Totals for badge update
        $total_inbox = (int)_cnt($conn->query("SELECT COUNT(*) c FROM admin_messages WHERE is_read=0 AND admin_reply IS NULL"));
        // Auto-reminder check — once per hour (non-blocking, silent)
        $auto_reminder_due = false;
        $conn->query("INSERT IGNORE INTO admin_settings (setting_key,setting_value) VALUES ('last_auto_reminder_run','0')");
        $lar_r=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='last_auto_reminder_run' LIMIT 1");
        $lar=(int)($lar_r?$lar_r->fetch_assoc()['setting_value']:0);
        if((time()-$lar)>=3600) $auto_reminder_due=true;
        echo json_encode([
            'ok'          => true,
            'new_inbox'   => $new_inbox,
            'total_inbox' => $total_inbox,
            'server_time' => time(),
            'auto_reminder_due' => $auto_reminder_due
        ]); exit();
    }

    // ── Clear Service Notifications ──────────────────────
    if($act==='clear_service_notifs' && $conn){
        requireSuperAdmin();
        $mode=trim($_POST['mode']??'all'); // all | read
        @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(`id` INT AUTO_INCREMENT PRIMARY KEY,`device_id` VARCHAR(100) NOT NULL,`type` VARCHAR(30) NOT NULL,`message` TEXT NOT NULL,`is_read` TINYINT DEFAULT 0,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if($mode==='read'){
            $conn->query("DELETE FROM service_notifications WHERE is_read=1");
        } else {
            $conn->query("DELETE FROM service_notifications");
        }
        $aff=(int)$conn->affected_rows;
        auditLog($conn,'CLEAR_SVC_NOTIFS',"mode:$mode deleted:$aff");
        echo json_encode(['ok'=>true,'msg'=>"✅ $aff টি service notification মুছে ফেলা হয়েছে।",'deleted'=>$aff]); exit();
    }

    // ── Bulk Delete ──────────────────────────────────────
    if($act==='del_multiple' && $conn){
        $table=trim($_POST['table']??'');
        $ids_raw=trim($_POST['ids']??'');
        $tableMap=['donors'=>['donors','del_donor'],'requests'=>['blood_requests','del_req'],'reports'=>['reports','del_report'],'calls'=>['call_logs','del_call'],'inbox'=>['admin_messages','del_inbox_msg'],'community'=>['community_posts','del_community_post']];
        if(!isset($tableMap[$table])){ echo json_encode(['ok'=>false,'msg'=>'Invalid table.']); exit(); }
        $tname=$tableMap[$table][0];
        // Parse and validate IDs
        $ids=array_filter(array_map('intval',explode(',',$ids_raw)),fn($x)=>$x>0);
        if(empty($ids)){ echo json_encode(['ok'=>false,'msg'=>'কোনো ID পাওয়া যায়নি।']); exit(); }
        // Requests carry attached image files — remove them from disk first.
        if($tname==='blood_requests') delReqDocFiles($conn,$ids);
        $placeholders=implode(',',array_fill(0,count($ids),'?'));
        $types=str_repeat('i',count($ids));
        $stmt=$conn->prepare("DELETE FROM `$tname` WHERE id IN ($placeholders)");
        $stmt->bind_param($types,...$ids);
        $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        if(method_exists($conn,'commit')) $conn->commit();
        auditLog($conn,'DEL_MULTIPLE',"table:$tname count:$affected ids:".implode(',',$ids));
        echo json_encode(['ok'=>true,'deleted'=>$affected]); exit();
    }

    // ── Standard CRUD actions ────────────────────────────
    if($conn && $id>=0){
        $stmt=null;
        if($act==='del_donor')       $stmt=$conn->prepare("DELETE FROM donors WHERE id=?");
        elseif($act==='del_req')   { delReqDocFiles($conn,[$id]); $stmt=$conn->prepare("DELETE FROM blood_requests WHERE id=?"); }
        elseif($act==='fulfill_req') $stmt=$conn->prepare("UPDATE blood_requests SET status='Fulfilled' WHERE id=?");
        elseif($act==='del_report')  $stmt=$conn->prepare("DELETE FROM reports WHERE id=?");
        elseif($act==='del_call')    $stmt=$conn->prepare("DELETE FROM call_logs WHERE id=?");
        elseif($act==='del_community_post') $stmt=$conn->prepare("DELETE FROM community_posts WHERE id=?");

        if($stmt){
            $stmt->bind_param("i",$id);
            $exec_ok=$stmt->execute();
            $affected=$stmt->affected_rows;
            $stmt->close();
            if(!$exec_ok||$affected<1){
                echo json_encode(['ok'=>false,'msg'=>'⚠️ Delete হয়নি — row পাওয়া যায়নি বা DB error। (affected:'.$affected.')']);
                exit();
            }
            if(method_exists($conn,'commit')) $conn->commit();
            auditLog($conn,strtoupper($act),"id:$id affected:{$affected}");
            echo json_encode(['ok'=>true]); exit();
        } else {
            echo json_encode(['ok'=>false,'msg'=>'DB prepare error: '.($conn->error??'unknown')]);
            exit();
        }
    }
    echo json_encode(['ok'=>false,'msg'=>'Invalid ID বা DB unavailable']); exit();
}

// ── 14. FETCH DATA ────────────────────────────────────────
$donors=$requests=$reports=$calls=$audit_log=$ip_list=$token_list=[];
$stats=['donors'=>0,'available'=>0,'calls'=>0,'active_req'=>0,'reports'=>0,'week'=>0];
$ip_whitelist_enabled=false;

// One-time badge sync
if($conn){
    $_adm_schema_v2=__DIR__.'/.schema_v2_done';
    if(!file_exists($_adm_schema_v2)){
        @$conn->query("UPDATE donors SET badge_level=CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END");
        @file_put_contents($_adm_schema_v2,date('Y-m-d H:i:s'));
    }
}

if($logged_in && $conn){
    ensureAdminTables($conn);

    $stats['donors']    =(int)_cnt(dbq($conn,"SELECT COUNT(*) c FROM donors"));
    $r=dbq($conn,"SELECT COUNT(*) c FROM donors WHERE willing_to_donate='yes' AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120)");
    $stats['available'] =$r?(int)$r->fetch_assoc()['c']:0;
    $r2=dbq($conn,"SELECT COUNT(*) c FROM call_logs");
    $stats['calls']     =$r2?(int)$r2->fetch_assoc()['c']:0;
    $r3=dbq($conn,"SELECT COUNT(*) c FROM donors WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
    $stats['week']      =$r3?(int)$r3->fetch_assoc()['c']:0;


    $res=dbq($conn,"SELECT id,name,blood_group,location,phone,last_donation,willing_to_donate,total_donations,badge_level,reg_ip,reg_geo,created_at FROM donors ORDER BY id DESC LIMIT 300");
    if(!$res) $res=dbq($conn,"SELECT id,name,blood_group,location,phone,last_donation,willing_to_donate,total_donations,badge_level FROM donors ORDER BY id DESC LIMIT 300");
    if($res) while($row=$res->fetch_assoc()) $donors[]=$row;

    $conn->query("CREATE TABLE IF NOT EXISTS `blood_requests`(`id` INT AUTO_INCREMENT PRIMARY KEY,`patient_name` VARCHAR(100),`blood_group` VARCHAR(5),`hospital` VARCHAR(200),`contact` VARCHAR(20),`urgency` VARCHAR(10) DEFAULT 'High',`bags_needed` INT DEFAULT 1,`note` VARCHAR(500) DEFAULT '',`status` VARCHAR(20) DEFAULT 'Active',`req_ip` VARCHAR(50) DEFAULT NULL,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("ALTER TABLE `blood_requests` AUTO_INCREMENT=1");
    $res2=dbq($conn,"SELECT * FROM blood_requests ORDER BY FIELD(status,'Active','Fulfilled','Expired'),created_at DESC LIMIT 100");
    if($res2) while($row=$res2->fetch_assoc()) $requests[]=$row;
    $stats['active_req']=count(array_filter($requests,function($r){return $r['status']==='Active';}));

    // Attached document tokens per request (request_documents — created by index.php schema v6)
    $reqDocs=[];
    if($requests){
        $ids=implode(',', array_map(function($r){return (int)$r['id'];}, $requests));
        $rd=dbq($conn,"SELECT request_id, token FROM request_documents WHERE request_id IN ($ids) ORDER BY id ASC");
        if($rd) while($d=$rd->fetch_assoc()) $reqDocs[(int)$d['request_id']][]=$d['token'];
    }

    $inbox_unread=(int)_cnt(dbq($conn,"SELECT COUNT(*) c FROM admin_messages WHERE is_read=0 AND admin_reply IS NULL"));

    $res3=dbq($conn,"SELECT * FROM reports ORDER BY id DESC LIMIT 100");
    if($res3) while($row=$res3->fetch_assoc()) $reports[]=$row;
    $stats['reports']=count($reports);

    $conn->query("ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS caller_location VARCHAR(500) DEFAULT 'Not provided'");
    $conn->query("ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS device_info VARCHAR(300) DEFAULT NULL");
    $res4=dbq($conn,"SELECT cl.*,d.name donor_name,d.blood_group FROM call_logs cl LEFT JOIN donors d ON cl.donor_id=d.id ORDER BY cl.id DESC LIMIT 200");
    if($res4) while($row=$res4->fetch_assoc()) $calls[]=$row;

    // Community posts
    $community_posts=[];
    $cr=dbq($conn,"SELECT cp.*,(SELECT COUNT(*) FROM community_replies WHERE post_id=cp.id) as reply_count FROM community_posts cp ORDER BY cp.id DESC LIMIT 100");
    if($cr) while($row=$cr->fetch_assoc()) $community_posts[]=$row;

    ensureAuditTable($conn);
    $res5=dbq($conn,"SELECT * FROM admin_audit_log ORDER BY id DESC LIMIT 50");
    if($res5) while($row=$res5->fetch_assoc()) $audit_log[]=$row;

    // IP whitelist data
    $wlSetting=$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key='ip_whitelist_enabled' LIMIT 1");
    if($wlSetting){ $wlRow=$wlSetting->fetch_assoc(); $ip_whitelist_enabled=($wlRow['setting_value']??'0')==='1'; }
    $irAll=$conn->query("SELECT * FROM ip_whitelist ORDER BY id DESC");
    if($irAll) while($row=$irAll->fetch_assoc()) $ip_list[]=$row;

    // Token data
    $trAll=$conn->query("SELECT id,token_name,token_value,is_active,created_at,last_used FROM api_tokens ORDER BY id DESC");
    if($trAll) while($row=$trAll->fetch_assoc()) $token_list[]=$row;
}

$CSRF=$_SESSION['csrf'];
$currentIP=getIP();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin — Blood Solution</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0f1115;--card:#1a1d24;--card2:#1e2230;--bdr:rgba(255,255,255,0.08);
  --red:#dc2626;--green:#10b981;--blue:#3b82f6;--orange:#f59e0b;--purple:#8b5cf6;--cyan:#06b6d4;
  --text:#f3f4f6;--muted:#9ca3af;--inp:rgba(0,0,0,0.35);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

/* ── LOGIN ── */
.lp{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.lb{background:var(--card);border:1px solid var(--bdr);border-radius:20px;padding:40px;width:100%;max-width:360px;text-align:center;}
.lb h1{color:var(--red);font-size:1.6rem;margin-bottom:4px;}
.lb p{color:var(--muted);font-size:.87em;margin-bottom:26px;}
.lb input[type=password]{width:100%;padding:13px;background:var(--inp);border:1px solid var(--bdr);border-radius:10px;color:var(--text);font-size:1rem;margin-bottom:10px;outline:none;}
.lb input[type=password]:focus{border-color:var(--red);}
.lb button{width:100%;padding:13px;background:var(--red);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;}
.lb button:hover{background:#b91c1c;}
.err{color:var(--red);background:rgba(220,38,38,.1);border-radius:8px;padding:9px;margin-bottom:10px;font-size:.86em;}
.warn-box{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:var(--orange);border-radius:8px;padding:9px;margin-bottom:10px;font-size:.84em;}
.lock-box{background:rgba(239,68,68,.08);border:2px solid rgba(239,68,68,.3);border-radius:12px;padding:20px;margin-bottom:12px;text-align:center;}
.lock-box .lock-icon{font-size:2.5rem;margin-bottom:8px;}
.lock-box p{color:var(--red);font-weight:600;margin-bottom:4px;}
.lock-box small{color:var(--muted);font-size:.82em;}
.honey{display:none;}

/* ── IDLE WARNING ── */
.idle-warn{position:fixed;top:56px;right:16px;background:rgba(245,158,11,.96);color:#000;padding:10px 16px;border-radius:10px;font-size:.83em;font-weight:700;z-index:9999;display:none;box-shadow:0 4px 15px rgba(0,0,0,.4);}

/* ── HEADER ── */
.hdr{background:#141720;border-bottom:1px solid var(--bdr);padding:10px 18px;display:flex;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:200;height:48px;}
.hdr h1{color:var(--red);font-size:1rem;font-weight:800;}
.hdr small{color:var(--muted);font-size:.75em;margin-left:6px;}
.hdr-right{display:flex;align-items:center;gap:10px;}
.session-info{font-size:.72em;color:var(--muted);background:rgba(255,255,255,.04);padding:4px 9px;border-radius:8px;}
.lout{background:rgba(220,38,38,.12);color:var(--red);border:1px solid rgba(220,38,38,.25);padding:5px 12px;border-radius:20px;font-size:.78em;font-weight:600;cursor:pointer;text-decoration:none;}

/* ── LAYOUT ── */
.layout{display:flex;padding-top:48px;}
.sb{width:200px;background:#141720;border-right:1px solid var(--bdr);min-height:calc(100vh - 48px);padding:8px 0;flex-shrink:0;position:fixed;top:48px;left:0;height:calc(100vh - 48px);overflow-y:auto;}
.main{flex:1;padding:20px;margin-left:200px;overflow:auto;min-height:calc(100vh - 48px);}
.ni{display:flex;align-items:center;gap:7px;padding:10px 16px;cursor:pointer;font-size:.86em;color:var(--muted);border-left:3px solid transparent;transition:all .14s;}
.ni:hover{color:var(--text);background:rgba(255,255,255,.04);}
.ni.on{color:var(--text);background:rgba(220,38,38,.09);border-left-color:var(--red);}
.ni .cnt{margin-left:auto;background:var(--red);color:#fff;font-size:.67em;padding:1px 6px;border-radius:10px;font-weight:700;}
.ni-sep{height:1px;background:var(--bdr);margin:6px 12px;}
.ni-label{padding:8px 16px 2px;font-size:.65em;text-transform:uppercase;letter-spacing:1.5px;color:rgba(156,163,175,.45);font-weight:700;}

/* ── MOBILE TABS ── */
.mtabs{display:none;overflow-x:auto;gap:5px;padding:8px 10px;background:#141720;border-bottom:1px solid var(--bdr);position:fixed;top:48px;left:0;right:0;z-index:190;}
.mt{flex-shrink:0;padding:6px 12px;background:var(--inp);border:1px solid var(--bdr);border-radius:20px;color:var(--muted);font-size:.78em;cursor:pointer;white-space:nowrap;}
.mt.on{background:var(--red);color:#fff;border-color:var(--red);}
@media(max-width:700px){
  .sb{display:none;}
  .mtabs{display:flex;}
  .main{margin-left:0;padding:12px;padding-top:56px;}
  .layout{padding-top:48px;}
}

/* ── TABS ── */
.tab{display:none;}.tab.on{display:block;}

/* ── STATS ── */
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:20px;}
.sc{background:var(--card);border:1px solid var(--bdr);border-radius:12px;padding:14px;text-align:center;}
.sc .v{font-size:1.8rem;font-weight:800;line-height:1;}
.sc .l{font-size:.73em;color:var(--muted);margin-top:4px;}
.sc.r .v{color:var(--red);}.sc.g .v{color:var(--green);}.sc.b .v{color:var(--blue);}.sc.o .v{color:var(--orange);}.sc.p .v{color:var(--purple);}

/* ── TABLE ── */
.tbox{background:var(--card);border:1px solid var(--bdr);border-radius:12px;overflow:hidden;margin-bottom:16px;}
.tbar{padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;border-bottom:1px solid var(--bdr);}
.tbar h3{font-size:.9rem;font-weight:700;}
.srch{background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:7px 11px;color:var(--text);font-size:.85em;outline:none;min-width:160px;}
.srch:focus{border-color:var(--red);}
.ow{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:rgba(220,38,38,.07);padding:8px 10px;text-align:left;font-size:.72em;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;}
th.cb-th{width:32px;text-align:center;padding:8px 6px;}
td{padding:8px 10px;font-size:.83em;border-bottom:1px solid var(--bdr);vertical-align:middle;}
td.cb-td{text-align:center;padding:8px 6px;width:32px;}
tr:hover td{background:rgba(255,255,255,.02);}
tr:last-child td{border-bottom:none;}
.bg{display:inline-block;padding:2px 7px;border-radius:20px;font-weight:700;font-size:.8em;background:rgba(220,38,38,.15);color:var(--red);}
.av{color:var(--green);font-weight:600;}.unav{color:var(--muted);}.nav2{color:var(--orange);}
.btn{padding:4px 9px;border:none;border-radius:7px;font-size:.76em;font-weight:600;cursor:pointer;white-space:nowrap;}
.bd{background:rgba(239,68,68,.15);color:var(--red);}.bd:hover{background:var(--red);color:#fff;}
.bo{background:rgba(16,185,129,.15);color:var(--green);}.bo:hover{background:var(--green);color:#fff;}
.bb{background:rgba(59,130,246,.15);color:var(--blue);}.bb:hover{background:var(--blue);color:#fff;}
.bpu{background:rgba(139,92,246,.15);color:var(--purple);}.bpu:hover{background:var(--purple);color:#fff;}
.bc{background:rgba(6,182,212,.15);color:var(--cyan);}.bc:hover{background:var(--cyan);color:#000;}
.bo2{background:rgba(245,158,11,.15);color:var(--orange);}.bo2:hover{background:var(--orange);color:#000;}
.uc{display:inline-block;padding:2px 7px;border-radius:10px;font-size:.76em;font-weight:700;}
.uc.cr{background:rgba(239,68,68,.15);color:var(--red);}
.uc.hi{background:rgba(245,158,11,.15);color:var(--orange);}
.uc.me{background:rgba(59,130,246,.15);color:var(--blue);}
.sp{display:inline-block;padding:2px 7px;border-radius:10px;font-size:.76em;font-weight:700;}
.sp.ac{background:rgba(16,185,129,.15);color:var(--green);}
.sp.fu,.sp.ex{background:rgba(107,114,128,.15);color:var(--muted);}
.empty{text-align:center;padding:36px;color:var(--muted);}
.stitle{font-size:1.2rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.bbar{display:flex;align-items:center;gap:6px;}
.bbar-in{flex:1;height:5px;background:rgba(255,255,255,.07);border-radius:3px;}
.bbar-fill{height:100%;background:var(--green);border-radius:3px;}
.audit-ev{display:inline-block;padding:2px 7px;border-radius:10px;font-size:.74em;font-weight:700;background:rgba(59,130,246,.12);color:var(--blue);}
.audit-ev.fail{background:rgba(239,68,68,.12);color:var(--red);}
.audit-ev.success{background:rgba(16,185,129,.12);color:var(--green);}

/* ── BULK DELETE BAR ── */
.bulk-bar{display:none;align-items:center;gap:8px;padding:8px 16px;background:rgba(239,68,68,.07);border-bottom:1px solid rgba(239,68,68,.15);}
.bulk-bar.show{display:flex;}
.bulk-bar span{font-size:.82em;color:var(--muted);}
.bulk-bar span strong{color:var(--text);}
.bulk-del-btn{padding:5px 14px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:.8em;font-weight:700;cursor:pointer;}
.bulk-del-btn:hover{background:#b91c1c;}
.bulk-cancel-btn{padding:5px 12px;background:rgba(255,255,255,.07);color:var(--muted);border:1px solid var(--bdr);border-radius:8px;font-size:.8em;cursor:pointer;}
input[type=checkbox].row-cb{width:15px;height:15px;cursor:pointer;accent-color:var(--red);}
input[type=checkbox]#cb-all-donors,input[type=checkbox]#cb-all-requests,input[type=checkbox]#cb-all-reports,input[type=checkbox]#cb-all-calls{width:15px;height:15px;cursor:pointer;accent-color:var(--red);}

/* ── DEVICE / LOCATION CELL ── */
.dev-cell{max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.74em;color:var(--muted);cursor:default;}
.loc-cell{max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.74em;color:var(--cyan);}

/* ── donor list cells ── */
.sc-search-bar{display:flex;flex-wrap:wrap;gap:8px;padding:14px 16px;border-bottom:1px solid var(--bdr);background:var(--card2);}
.sc-search-bar input,.sc-search-bar select{background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:7px 11px;color:var(--text);font-size:.84em;outline:none;min-width:130px;}
.sc-search-bar input:focus,.sc-search-bar select:focus{border-color:var(--purple);}
.sc-search-bar button{padding:7px 16px;border:none;border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;background:var(--purple);color:#fff;}
.code-hidden{font-family:monospace;letter-spacing:2px;color:var(--muted);font-size:.8em;}
.code-visible{font-family:monospace;letter-spacing:1px;color:var(--cyan);font-size:.8em;font-weight:700;}
.badge-pill{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.72em;font-weight:700;}
.bp-new{background:rgba(16,185,129,.15);color:var(--green);}
.bp-active{background:rgba(59,130,246,.15);color:var(--blue);}
.bp-hero{background:rgba(139,92,246,.15);color:var(--purple);}
.bp-legend{background:rgba(245,158,11,.15);color:var(--orange);}

/* ── ADVANCED SEARCH TAB ── */
.adv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;padding:16px;border-bottom:1px solid var(--bdr);background:var(--card2);}
.adv-grid label{font-size:.72em;color:var(--muted);display:block;margin-bottom:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.adv-grid input,.adv-grid select{width:100%;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:7px 10px;color:var(--text);font-size:.84em;outline:none;}
.adv-grid input:focus,.adv-grid select:focus{border-color:var(--blue);}
.adv-actions{padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.adv-actions button{padding:8px 18px;border:none;border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;}
.adv-run{background:var(--blue);color:#fff;}
.adv-clear{background:rgba(255,255,255,.07);color:var(--muted);border:1px solid var(--bdr);}
.adv-export{background:rgba(16,185,129,.15);color:var(--green);border:1px solid rgba(16,185,129,.3);}
.adv-count{margin-left:auto;font-size:.8em;color:var(--muted);}
#advResults{padding:0;}
.st-av{color:var(--green);font-weight:600;}.st-nw{color:var(--muted);}.st-na{color:var(--orange);}
.loading-spin{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.2);border-top-color:var(--blue);border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;margin-right:6px;}
@keyframes spin{to{transform:rotate(360deg);}}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;}
.modal{background:var(--card);border:1px solid var(--bdr);border-radius:16px;padding:24px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.6);}
.modal h3{font-size:1rem;font-weight:700;margin-bottom:12px;}
.modal .code-box{background:var(--inp);border:1px dashed var(--cyan);border-radius:10px;padding:14px;text-align:center;font-family:monospace;font-size:1.15rem;font-weight:700;color:var(--cyan);letter-spacing:2px;margin:12px 0;}
.modal-btns{display:flex;gap:8px;margin-top:14px;}
.modal-btns button{flex:1;padding:9px;border:none;border-radius:8px;font-size:.85em;font-weight:700;cursor:pointer;}

/* ── EDIT DONOR MODAL ── */
.edit-modal{background:var(--card);border:1px solid var(--bdr);border-radius:16px;padding:24px;max-width:540px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.7);max-height:90vh;overflow-y:auto;}
.edit-modal h3{font-size:1rem;font-weight:700;margin-bottom:16px;color:var(--text);}
.ef-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;}
.ef-row.full{grid-template-columns:1fr;}
.ef-label{font-size:.72em;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:3px;}
.ef-input{width:100%;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:8px 11px;color:var(--text);font-size:.88em;outline:none;}
.ef-input:focus{border-color:var(--blue);}
.ef-input[type=date]::-webkit-calendar-picker-indicator{filter:invert(0.6);}
.ef-toggle{display:flex;gap:6px;}
.ef-toggle button{flex:1;padding:7px;border:1px solid var(--bdr);border-radius:8px;font-size:.82em;font-weight:600;cursor:pointer;background:var(--inp);color:var(--muted);transition:all .15s;}
.ef-toggle button.on-yes{background:rgba(16,185,129,.2);border-color:var(--green);color:var(--green);}
.ef-toggle button.on-no{background:rgba(239,68,68,.2);border-color:var(--red);color:var(--red);}
.ef-save{width:100%;padding:11px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:.92em;font-weight:700;cursor:pointer;margin-top:14px;}
.ef-save:hover{background:#2563eb;}
.ef-cancel{width:100%;padding:9px;background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--bdr);border-radius:10px;font-size:.86em;font-weight:600;cursor:pointer;margin-top:6px;}
.ef-map-btn{padding:8px 11px;background:rgba(66,133,244,.12);border:1.5px solid rgba(66,133,244,.35);color:#4285f4;border-radius:8px;cursor:pointer;font-size:1.1rem;flex-shrink:0;}
.ef-geo-status{font-size:.72em;color:var(--green);margin-top:3px;display:none;}

/* ── SETTINGS / IP WHITELIST / TOKEN TABS ── */
.settings-section{background:var(--card);border:1px solid var(--bdr);border-radius:12px;padding:20px;margin-bottom:16px;}
.settings-section h4{font-size:.95rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.settings-section p{font-size:.83em;color:var(--muted);margin-bottom:14px;line-height:1.5;}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:12px;background:var(--card2);border-radius:10px;margin-bottom:12px;}
.toggle-row span{font-size:.88em;font-weight:600;}
.toggle-btn{position:relative;width:48px;height:26px;background:rgba(255,255,255,.1);border:none;border-radius:13px;cursor:pointer;transition:background .2s;flex-shrink:0;}
.toggle-btn.on{background:var(--green);}
.toggle-btn::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.3);}
.toggle-btn.on::after{transform:translateX(22px);}
.ip-add-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.ip-add-row input{flex:1;min-width:130px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:9px 12px;color:var(--text);font-size:.86em;outline:none;}
.ip-add-row input:focus{border-color:var(--green);}
.ip-add-row button{padding:9px 18px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:.85em;font-weight:700;cursor:pointer;white-space:nowrap;}
.ip-tag{display:flex;align-items:center;gap:8px;padding:9px 12px;background:var(--card2);border-radius:10px;margin-bottom:7px;border:1px solid var(--bdr);}
.ip-tag .ip-addr{font-family:monospace;font-size:.85em;color:var(--cyan);font-weight:700;}
.ip-tag .ip-label{font-size:.78em;color:var(--muted);flex:1;}
.ip-tag .ip-badge{font-size:.72em;padding:2px 7px;border-radius:8px;font-weight:700;}
.ip-tag .ip-badge.active{background:rgba(16,185,129,.15);color:var(--green);}
.ip-tag .ip-badge.inactive{background:rgba(107,114,128,.15);color:var(--muted);}
.ip-del-btn{padding:3px 9px;background:rgba(239,68,68,.15);color:var(--red);border:none;border-radius:6px;font-size:.75em;cursor:pointer;}
.ip-del-btn:hover{background:var(--red);color:#fff;}
.ip-mine{font-size:.78em;color:var(--muted);margin-bottom:12px;background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.15);border-radius:8px;padding:8px 12px;}
.ip-mine code{font-family:monospace;color:var(--blue);}

/* Password change form */
.pw-form{display:flex;flex-direction:column;gap:10px;}
.pw-form label{font-size:.72em;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:3px;}
.pw-form input{width:100%;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:9px 12px;color:var(--text);font-size:.88em;outline:none;}
.pw-form input:focus{border-color:var(--purple);}
.pw-save-btn{padding:11px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:.92em;font-weight:700;cursor:pointer;margin-top:4px;}
.pw-save-btn:hover{background:#7c3aed;}
.pw-result{margin-top:8px;font-size:.85em;padding:9px 12px;border-radius:8px;display:none;}
.pw-result.ok{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2);}
.pw-result.err{background:rgba(239,68,68,.1);color:var(--red);border:1px solid rgba(239,68,68,.2);}

/* Token tab */
.token-add-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;}
.token-add-row input{flex:1;min-width:150px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;padding:9px 12px;color:var(--text);font-size:.86em;outline:none;}
.token-add-row input:focus{border-color:var(--cyan);}
.token-add-row button{padding:9px 18px;background:var(--cyan);color:#000;border:none;border-radius:8px;font-size:.85em;font-weight:700;cursor:pointer;}
.token-row{display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--card2);border:1px solid var(--bdr);border-radius:10px;margin-bottom:8px;flex-wrap:wrap;}
.token-row .tok-name{font-weight:700;font-size:.88em;min-width:100px;}
.token-row .tok-val{font-family:monospace;font-size:.75em;color:var(--cyan);background:rgba(6,182,212,.08);padding:3px 8px;border-radius:6px;flex:1;word-break:break-all;}
.token-row .tok-status{font-size:.74em;padding:2px 8px;border-radius:8px;font-weight:700;flex-shrink:0;}
.tok-active{background:rgba(16,185,129,.15);color:var(--green);}
.tok-inactive{background:rgba(107,114,128,.15);color:var(--muted);}
.tok-date{font-size:.74em;color:var(--muted);white-space:nowrap;}
.tok-copy{padding:4px 10px;background:rgba(6,182,212,.12);color:var(--cyan);border:1px solid rgba(6,182,212,.25);border-radius:7px;font-size:.76em;cursor:pointer;}
.tok-toggle{padding:4px 10px;font-size:.76em;border:none;border-radius:7px;cursor:pointer;font-weight:600;}
.tok-toggle.active-btn{background:rgba(245,158,11,.15);color:var(--orange);}
.tok-toggle.inactive-btn{background:rgba(16,185,129,.15);color:var(--green);}
.tok-del{padding:4px 9px;background:rgba(239,68,68,.15);color:var(--red);border:none;border-radius:7px;font-size:.76em;cursor:pointer;}
.tok-del:hover{background:var(--red);color:#fff;}

/* Tab refresh button */
.tab-refresh-btn {
    padding:4px 12px; background:rgba(107,114,128,.12); color:var(--muted);
    border:1px solid var(--bdr); border-radius:20px; font-size:.76em;
    cursor:pointer; font-weight:600; transition:all .15s; white-space:nowrap;
}
.tab-refresh-btn:hover { background:rgba(59,130,246,.15); color:var(--blue); border-color:rgba(59,130,246,.3); }

.admin-footer{text-align:center;padding:18px;margin-top:28px;border-top:1px solid var(--bdr);font-size:.72em;color:rgba(156,163,175,.45);}
.admin-footer span{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.03);border:1px solid var(--bdr);border-radius:20px;padding:5px 14px;font-weight:500;letter-spacing:.3px;}
</style>
</head>
<body>

<?php if(!$logged_in): ?>
<!-- ══════════════ LOGIN ══════════════ -->
<div class="lp">
  <div class="lb">
    <div style="font-size:2.6rem;margin-bottom:8px;">🔐</div>
    <h1>Admin Panel</h1>
    <p>Blood Arena — Bangladesh</p>
    <?php if($db_error):?><div class="warn-box">⚠️ <?=esc($db_error)?></div><?php endif;?>
    <?php if($locked):?>
      <div class="lock-box">
        <div class="lock-icon">🔒</div>
        <p>অ্যাকাউন্ট সাময়িক বন্ধ!</p>
        <small>অনেক বেশি ভুল চেষ্টা। <span id="lockTimer"><?=ceil($lockout_left/60)?></span> মিনিট পর আবার।</small>
      </div>
    <?php else:?>
      <?php if($login_error):?><div class="err"><?=esc($login_error)?></div><?php endif;?>
      <?php if(isset($_GET['timeout'])):?><div class="err">⏰ Session শেষ — আবার login করুন।</div><?php endif;?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf" value="<?=esc($CSRF)?>">
        <div class="honey"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <input type="text" name="username" placeholder="👤 Username (Moderator only)" autocomplete="username" style="margin-bottom:8px;" tabindex="1">
        <input type="password" name="pass" placeholder="🔑 Password" required autocomplete="new-password" tabindex="2">
        <p style="font-size:.73em;color:var(--muted);margin-bottom:12px;margin-top:-4px;">Super Admin: Username ফাঁকা রেখে শুধু Password দিন</p>
        <button type="submit" name="admin_login">🔐 Secure Login</button>
      </form>
    <?php endif;?>
  </div>
</div>
<?php if($locked):?>
<script>
let s=<?=max(0,$lockout_left)?>;const el=document.getElementById('lockTimer');
if(el){const t=setInterval(()=>{s--;if(el)el.textContent=Math.ceil(s/60);if(s<=0){clearInterval(t);location.reload();}},1000);}
</script>
<?php endif;?>
<div class="admin-footer" style="position:fixed;bottom:0;left:0;right:0;padding:10px;"><span>🩸 © 2026 Siam Innovatives — All Rights Reserved.</span></div>

<?php else: ?>
<!-- ══════════════ ADMIN PANEL ══════════════ -->
<div class="idle-warn" id="idleWarn">⚠️ <span id="idleSecs"></span>s এ auto-logout!</div>

<div class="hdr">
  <div><h1>🩸 BloodArena Admin</h1><small>Bangladesh</small></div>
  <div class="hdr-right">
    <?php if($is_super):?>
    <span style="font-size:.72em;background:rgba(220,38,38,.15);color:#ef4444;border:1px solid rgba(220,38,38,.3);padding:3px 10px;border-radius:20px;font-weight:700;">👑 Super Admin</span>
    <?php else:?>
    <span style="font-size:.72em;background:rgba(59,130,246,.15);color:#3b82f6;border:1px solid rgba(59,130,246,.3);padding:3px 10px;border-radius:20px;font-weight:700;">🛡️ Moderator</span>
    <?php endif;?>
    <span class="session-info" id="sessionTimer">⏱ <span id="sClock"></span></span>
    <button onclick="toggleAppLanguage()" title="বাংলা / English" style="font-size:.72em;background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);padding:3px 10px;border-radius:20px;font-weight:700;cursor:pointer;">🌐 <span id="langCurrentBadge">বাংলা</span></button>
    <a href="admin.php?logout=1" class="lout">🚪 Logout</a>
  </div>
</div>

<div class="mtabs" id="mtabs">
  <div class="mt on"  onclick="go('dashboard',this)">📊 Dashboard</div>
  <div class="mt"     onclick="go('donors',this)">👥 Donors</div>
  <div class="mt"     onclick="go('requests',this)">🆘 Requests</div>
  <div class="mt"     onclick="go('reports',this)">⚠️ Reports</div>
  <div class="mt"     onclick="go('calls',this)">📞 Calls</div>
  <div class="mt"     onclick="go('community',this)">💬 Community</div>
  <div class="mt"     onclick="go('notifications',this)">🔔 Notif</div>
  <?php if($is_super):?><div class="mt" onclick="go('inbox',this)">📬 Inbox<?php if(!empty($inbox_unread)&&$inbox_unread>0):?> (<?=$inbox_unread?>)<?php endif;?></div><?php endif;?>
  <?php if($is_super):?><div class="mt" onclick="go('moderators',this)">👥 Mods</div><?php endif;?>
  <div class="mt"     onclick="go('tokens',this)">🪙 Tokens</div>
  <div class="mt"     onclick="go('advsearch',this)">🔍 Search</div>
  <div class="mt"     onclick="go('audit',this)">📋 Audit</div>
  <?php if($is_super):?><div class="mt" onclick="go('selfreports',this); loadSelfReports();">🩸 Self-Rep</div><?php endif;?>
  <div class="mt"     onclick="go('settings',this)">⚙️ Settings</div>
</div>

<div class="layout">
  <nav class="sb">
    <div class="ni-label">Main</div>
    <div class="ni on" onclick="go('dashboard',this)">📊 Dashboard</div>
    <div class="ni"    onclick="go('donors',this)">👥 Donors <span class="cnt"><?=count($donors)?></span></div>
    <div class="ni"    onclick="go('requests',this)">🆘 Requests <span class="cnt"><?=$stats['active_req']?></span></div>
    <div class="ni"    onclick="go('reports',this)">⚠️ Reports <?php if($stats['reports']>0):?><span class="cnt"><?=$stats['reports']?></span><?php endif;?></div>
    <div class="ni"    onclick="go('calls',this)">📞 Call Logs</div>
    <div class="ni"    onclick="go('community',this)">💬 Community</div>
    <div class="ni-sep"></div>
    <div class="ni-label">Tools</div>
    <div class="ni"    onclick="go('notifications',this)">🔔 Notifications</div>
    <?php if($is_super):?><div class="ni" onclick="go('inbox',this)">📬 Inbox <?php if(!empty($inbox_unread)&&$inbox_unread>0):?><span class="cnt"><?=$inbox_unread?></span><?php endif;?></div><?php endif;?>
    <div class="ni"    onclick="go('tokens',this)">🪙 Token Manager</div>
    <?php if($is_super):?>
    <div class="ni"    onclick="go('moderators',this)">👥 Moderators</div>
    <?php endif;?>
    <div class="ni"    onclick="go('advsearch',this)">🔍 Advanced Search</div>
    <div class="ni-sep"></div>
    <div class="ni-label">System</div>
    <div class="ni"    onclick="go('audit',this)">📋 Audit Log</div>
    <?php if($is_super):?><div class="ni" onclick="go('selfreports',this); loadSelfReports();">🩸 Self-Reported Donations</div><?php endif;?>
    <div class="ni"    onclick="go('settings',this)">⚙️ Settings <span id="adminNotifDot" style="display:none;width:7px;height:7px;background:var(--green);border-radius:50%;margin-left:auto;flex-shrink:0;"></span></div>
    <div style="padding:12px 16px;margin-top:6px;border-top:1px solid var(--bdr);">
      <a href="index.php" style="font-size:.78em;color:var(--muted);display:block;margin-bottom:6px;">🌐 Site দেখুন</a>
      <span style="font-size:.7em;color:rgba(100,116,139,.5);">IP: <?=esc(getIP())?></span>
    </div>
  </nav>

  <main class="main">
    <?php if($db_error):?><div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:var(--orange);border-radius:10px;padding:11px;margin-bottom:14px;font-size:.84em;">⚠️ DB: <?=esc($db_error)?></div><?php endif;?>

    <!-- ══ DASHBOARD ══ -->
    <div class="tab on" id="tab-dashboard">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">📊 Dashboard<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="stats">
        <div class="sc r"><div class="v"><?=$stats['donors']?></div><div class="l">মোট Donors</div></div>
        <div class="sc g"><div class="v"><?=$stats['available']?></div><div class="l">Available</div></div>
        <div class="sc o"><div class="v"><?=$stats['active_req']?></div><div class="l">Active Requests</div></div>
        <div class="sc b"><div class="v"><?=$stats['calls']?></div><div class="l">Total Calls</div></div>
        <div class="sc p"><div class="v"><?=$stats['reports']?></div><div class="l">Reports</div></div>
        <div class="sc g"><div class="v"><?=$stats['week']?></div><div class="l">এ সপ্তাহে নতুন</div></div>
        <?php if($is_super && !empty($inbox_unread) && $inbox_unread>0):?>
        <div class="sc b" onclick="go('inbox',document.querySelector('.ni[onclick*=inbox]'))" style="cursor:pointer;border-color:rgba(59,130,246,.4);position:relative;">
          <div class="v" style="color:var(--blue);"><?=$inbox_unread?></div>
          <div class="l">📬 Unread Inbox</div>
        </div>
        <?php endif;?>
      </div>
      <div class="tbox">
        <div class="tbar"><h3>🩸 Blood Group Summary</h3></div>
        <div class="ow"><table>
          <thead><tr><th>Group</th><th>Total</th><th>Available</th><th>%</th></tr></thead>
          <tbody>
          <?php if($conn): foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g):
            $eg=mysqli_real_escape_string($conn,$g);
            $tot=(int)_cnt(dbq($conn,"SELECT COUNT(*) c FROM donors WHERE blood_group='$eg'"));
            $avr=dbq($conn,"SELECT COUNT(*) c FROM donors WHERE blood_group='$eg' AND willing_to_donate='yes' AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120)");
            $av=$avr?(int)$avr->fetch_assoc()['c']:0;
            $pct=$tot>0?round($av/$tot*100):0;
          ?><tr>
            <td><span class="bg"><?=$g?></span></td>
            <td><?=$tot?></td><td class="av"><?=$av?></td>
            <td><div class="bbar"><div class="bbar-in"><div class="bbar-fill" style="width:<?=$pct?>%;"></div></div><span style="font-size:.76em;color:var(--muted);"><?=$pct?>%</span></div></td>
          </tr><?php endforeach; endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ DONORS ══ -->
    <div class="tab" id="tab-donors">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">👥 Donors <span style="font-size:.65em;color:var(--muted);font-weight:400;">(<?=count($donors)?>)</span><button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar">
          <h3>All Donors</h3>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <input class="srch" placeholder="🔍 নাম / ফোন / লোকেশন..." oninput="ft('dtb',this.value)">
          </div>
        </div>
        <div class="bulk-bar" id="bulk-bar-donors" <?php if($is_moderator) echo 'style="display:none!important;"'; ?>>
          <span>✅ <strong id="donors-sel-count">0</strong> টি selected</span>
          <button class="bulk-del-btn" onclick="bulkDelete('donors')">🗑 Delete Selected</button>
          <button style="padding:5px 14px;background:rgba(59,130,246,.15);color:var(--blue);border:1px solid rgba(59,130,246,.3);border-radius:8px;font-size:.8em;font-weight:700;cursor:pointer;" onclick="openNotifySelectedModal()">📢 Notify Selected</button>
          <button class="bulk-cancel-btn" onclick="clearSelection('donors')">✕ Cancel</button>
        </div>
        <div class="ow"><table>
          <thead><tr>
            <th class="cb-th"><input type="checkbox" id="cb-all-donors" onchange="toggleAll('donors',this.checked)"></th>
            <th>#</th><th>Name</th><th>Group</th><th>Phone</th><th>Location</th><th>Status</th><th>Badge</th><th>Donations</th><th>Joined</th><th>Reg IP</th><th>📍 Reg Location</th><th>Actions</th>
          </tr></thead>
          <tbody id="dtb">
          <?php
          $badge_icons=['Legend'=>'👑','Hero'=>'🦸','Active'=>'⭐','New'=>'🌱'];
          foreach($donors as $i=>$d):
            $w=$d['willing_to_donate']??'yes'; $ld=$d['last_donation']??'no';
            $isav=($ld==='no'||empty($ld)||$ld==='0000-00-00'||(strtotime($ld)&&(time()-strtotime($ld))/86400>=120));
            $cls=$w==='no'?'unav':($isav?'av':'nav2');
            $stx=$w==='no'?'⛔ Not Willing':($isav?'✔ Available':'✖ Not Available');
            $jn=!empty($d['created_at'])?date('d M Y',strtotime($d['created_at'])):'—';
            $rip=esc($d['reg_ip']??'—');
            $rgeo=$d['reg_geo']??'Not captured';
            $hasLatLng=preg_match('/Lat:\s*([\-0-9.]+),\s*Lon:\s*([\-0-9.]+)/',$rgeo,$gm2);
            $mapsUrl=$hasLatLng?'https://www.google.com/maps?q='.$gm2[1].','.$gm2[2]:'https://www.google.com/maps/search/'.urlencode($d['location']??'');
            $rgeoLabel=$hasLatLng?$gm2[1].','.$gm2[2]:'—';
            $locMapsUrl='https://www.google.com/maps/search/'.urlencode($d['location']??'');
          ?><tr id="drow<?=$d['id']?>" class="donor-row">
            <td class="cb-td"><input type="checkbox" class="row-cb donors-cb" value="<?=$d['id']?>" onchange="onRowCbChange('donors')"></td>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td style="font-weight:600;white-space:nowrap;"><?=esc($d['name'])?></td>
            <td><span class="bg"><?=esc($d['blood_group'])?></span></td>
            <td style="font-family:monospace;font-size:.8em;"><?=esc($d['phone'])?></td>
            <td style="font-size:.8em;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <a href="<?=esc($locMapsUrl)?>" target="_blank" rel="noopener" title="Google Maps" style="color:var(--cyan);text-decoration:none;">📍 <?=esc($d['location']??'')?></a>
            </td>
            <td class="<?=$cls?>" style="white-space:nowrap;"><?=$stx?></td>
            <td style="font-size:.79em;"><?=($badge_icons[$d['badge_level']??'New']??'🌱')?> <?=esc($d['badge_level']??'New')?></td>
            <td style="text-align:center;"><?=(int)($d['total_donations']??0)?></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$jn?></td>
            <td style="font-family:monospace;font-size:.74em;color:var(--muted);"><?=$rip?></td>
            <td style="font-size:.73em;white-space:nowrap;">
              <?php if($hasLatLng):?>
                <a href="<?=esc($mapsUrl)?>" target="_blank" rel="noopener" style="color:var(--green);text-decoration:none;font-weight:600;" title="Google Maps exact location">🗺️ <?=esc($rgeoLabel)?></a>
              <?php else:?><span style="color:var(--muted);">—</span><?php endif;?>
            </td>
            <td>
              <div style="display:flex;gap:4px;">
                <button class="btn bb" onclick="openEditDonor(<?=$d['id']?>,this)" title="Edit">✏️</button>
                <?php if($is_super):?><button class="btn bd" onclick="act('del_donor',<?=$d['id']?>,this,'drow<?=$d['id']?>')">🗑</button><?php endif;?>
              </div>
            </td>
          </tr><?php endforeach; if(empty($donors)):?><tr><td colspan="13" class="empty">কোনো donor নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ REQUESTS ══ -->
    <div class="tab" id="tab-requests">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">🆘 Blood Requests <span style="font-size:.65em;color:var(--muted);font-weight:400;">(<?=count($requests)?>)</span><button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar">
          <h3>All Requests</h3>
          <input class="srch" placeholder="🔍 খুঁজুন..." oninput="ft('rtb',this.value)">
        </div>
        <div class="bulk-bar" id="bulk-bar-requests" <?php if($is_moderator) echo 'style="display:none!important;"'; ?>>
          <span>✅ <strong id="requests-sel-count">0</strong> টি selected</span>
          <button class="bulk-del-btn" onclick="bulkDelete('requests')">🗑 Delete Selected</button>
          <button class="bulk-cancel-btn" onclick="clearSelection('requests')">✕ Cancel</button>
        </div>
        <div class="ow"><table>
          <thead><tr>
            <th class="cb-th"><input type="checkbox" id="cb-all-requests" onchange="toggleAll('requests',this.checked)"></th>
            <th>#</th><th>Patient</th><th>Group</th><th>Hospital</th><th>Contact</th><th>Urgency</th><th>Bags</th><th>Required</th><th>Status</th><th>Time</th><th>Actions</th>
          </tr></thead>
          <tbody id="rtb">
          <?php foreach($requests as $i=>$r):
            $uc=strtolower($r['urgency']??'high');
            if(!in_array($uc,['critical','high','medium'])) $uc='high';
            $ucmap=['critical'=>'cr','high'=>'hi','medium'=>'me'];
            $tm=!empty($r['created_at'])?date('d M, h:i A',strtotime($r['created_at'])):'—';
            $rq=!empty($r['required_at'])?date('d M, h:i A',strtotime($r['required_at'])):'—';
          ?><tr id="rr<?=$r['id']?>" class="requests-row">
            <td class="cb-td"><input type="checkbox" class="row-cb requests-cb" value="<?=$r['id']?>" onchange="onRowCbChange('requests')"></td>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td style="font-weight:600;"><?=esc($r['patient_name'])?></td>
            <td><span class="bg"><?=esc($r['blood_group'])?></span></td>
            <td style="font-size:.81em;"><?=esc($r['hospital'])?></td>
            <td style="font-family:monospace;font-size:.8em;"><?=esc($r['contact'])?></td>
            <td><span class="uc <?=$ucmap[$uc]?>"><?=esc($r['urgency'])?></span></td>
            <td style="text-align:center;"><?=(int)($r['bags_needed']??1)?></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$rq?></td>
            <td><span class="sp <?=strtolower(substr($r['status']??'Active',0,2))?>"><?=esc($r['status']??'Active')?></span></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$tm?></td>
            <td>
              <div style="display:flex;gap:4px;align-items:center;">
                <?php $docs=$reqDocs[(int)$r['id']]??[]; foreach($docs as $tok): ?>
                  <a href="/?req_doc=<?=esc($tok)?>" target="_blank" rel="noopener" title="সংযুক্ত ছবি দেখুন">
                    <img src="/?req_doc=<?=esc($tok)?>" alt="doc" loading="lazy" style="width:34px;height:34px;object-fit:cover;border-radius:6px;border:1px solid var(--border);cursor:zoom-in;">
                  </a>
                <?php endforeach; ?>
                <?php if(($r['status']??'')==='Active'):?><button class="btn bo" onclick="act('fulfill_req',<?=$r['id']?>,this,'rr<?=$r['id']?>')">✅</button><?php endif;?>
                <?php if($is_super):?><button class="btn bd" onclick="act('del_req',<?=$r['id']?>,this,'rr<?=$r['id']?>')">🗑</button><?php endif;?>
              </div>
            </td>
          </tr><?php endforeach; if(empty($requests)):?><tr><td colspan="12" class="empty">কোনো request নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ REPORTS ══ -->
    <div class="tab" id="tab-reports">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">⚠️ Reports <span style="font-size:.65em;color:var(--muted);font-weight:400;">(<?=count($reports)?>)</span><button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar"><h3>Harassment Reports</h3></div>
        <div class="bulk-bar" id="bulk-bar-reports" <?php if($is_moderator) echo 'style="display:none!important;"'; ?>>
          <span>✅ <strong id="reports-sel-count">0</strong> টি selected</span>
          <button class="bulk-del-btn" onclick="bulkDelete('reports')">🗑 Delete Selected</button>
          <button class="bulk-cancel-btn" onclick="clearSelection('reports')">✕ Cancel</button>
        </div>
        <div class="ow"><table>
          <thead><tr>
            <th class="cb-th"><input type="checkbox" id="cb-all-reports" onchange="toggleAll('reports',this.checked)"></th>
            <th>#</th><th>Donor Phone</th><th>Harasser Info</th><th>Comment</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach($reports as $i=>$r):?><tr id="pr<?=$r['id']?>" class="reports-row">
            <td class="cb-td"><input type="checkbox" class="row-cb reports-cb" value="<?=$r['id']?>" onchange="onRowCbChange('reports')"></td>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td style="font-family:monospace;"><?=esc($r['donor_phone']??'')?></td>
            <td style="font-size:.81em;max-width:160px;"><?=esc($r['harasser_info']??'')?></td>
            <td style="font-size:.8em;color:var(--muted);max-width:220px;"><?=esc($r['report_comment']??'')?></td>
            <td><?php if($is_super):?><button class="btn bd" onclick="act('del_report',<?=$r['id']?>,this,'pr<?=$r['id']?>')">🗑</button><?php endif;?></td>
          </tr><?php endforeach; if(empty($reports)):?><tr><td colspan="6" class="empty">🕊️ কোনো report নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ CALL LOGS ══ -->
    <div class="tab" id="tab-calls">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">📞 Call Logs <span style="font-size:.65em;color:var(--muted);font-weight:400;">(<?=count($calls)?>)</span><button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar">
          <h3>Recent Calls</h3>
          <input class="srch" placeholder="🔍 Donor / Caller / IP..." oninput="ft('ctb',this.value)">
        </div>
        <div class="bulk-bar" id="bulk-bar-calls" <?php if($is_moderator) echo 'style="display:none!important;"'; ?>>
          <span>✅ <strong id="calls-sel-count">0</strong> টি selected</span>
          <button class="bulk-del-btn" onclick="bulkDelete('calls')">🗑 Delete Selected</button>
          <button class="bulk-cancel-btn" onclick="clearSelection('calls')">✕ Cancel</button>
        </div>
        <div class="ow"><table>
          <thead><tr>
            <th class="cb-th"><input type="checkbox" id="cb-all-calls" onchange="toggleAll('calls',this.checked)"></th>
            <th>#</th><th>Donor</th><th>Group</th><th>Caller Name</th><th>Caller Phone</th><th>IP</th><th>📍 Location</th><th>📱 Device</th><th>Time</th><th>Del</th>
          </tr></thead>
          <tbody id="ctb">
          <?php foreach($calls as $i=>$c):
            $tm=!empty($c['created_at'])?date('d M, h:i A',strtotime($c['created_at'])):'—';
            $loc=$c['caller_location']??'Not provided';
            $dev=$c['device_info']??'';
            $devShort='Unknown';
            if($dev){
                if(preg_match('/iPhone|iPad/i',$dev)) $devShort='📱 iOS';
                elseif(preg_match('/Android/i',$dev)){ preg_match('/Android\s[\d.]+.*?;\s([^;)]+)/i',$dev,$dm); $devShort='🤖 '.trim($dm[1]??'Android'); }
                elseif(preg_match('/Windows/i',$dev))  $devShort='🖥️ Windows';
                elseif(preg_match('/Macintosh|Mac OS/i',$dev)) $devShort='🍎 Mac';
                elseif(preg_match('/Linux/i',$dev))    $devShort='🐧 Linux';
                else $devShort=mb_substr($dev,0,30,'UTF-8');
                if(preg_match('/Chrome\/([\d.]+)/i',$dev)) $devShort.=' / Chrome';
                elseif(preg_match('/Firefox\/([\d.]+)/i',$dev)) $devShort.=' / Firefox';
                elseif(preg_match('/Safari\/([\d.]+)/i',$dev)&&!preg_match('/Chrome/i',$dev)) $devShort.=' / Safari';
            }
            $locShort=$loc==='Not provided'?'—':mb_substr($loc,0,50,'UTF-8');
            $locMapsUrl='';
            if($loc!=='Not provided'&&!empty($loc)){
                if(preg_match('/Lat:\s*([\-0-9.]+),\s*Lon:\s*([\-0-9.]+)/i',$loc,$lm)){
                    $locMapsUrl='https://www.google.com/maps?q='.$lm[1].','.$lm[2];
                } else {
                    $locMapsUrl='https://www.google.com/maps/search/'.urlencode($loc);
                }
            }
          ?><tr id="crow<?=$c['id']?>" class="calls-row">
            <td class="cb-td"><input type="checkbox" class="row-cb calls-cb" value="<?=$c['id']?>" onchange="onRowCbChange('calls')"></td>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td style="font-weight:600;white-space:nowrap;"><?=esc($c['donor_name']??'—')?></td>
            <td><span class="bg"><?=esc($c['blood_group']??'—')?></span></td>
            <td><?=esc($c['caller_name']??'')?></td>
            <td style="font-family:monospace;font-size:.8em;"><?=esc($c['caller_phone']??'')?></td>
            <td style="font-family:monospace;font-size:.74em;color:var(--muted);"><?=esc($c['caller_ip']??'—')?></td>
            <td><?php if($locMapsUrl):?><a href="<?=esc($locMapsUrl)?>" target="_blank" rel="noopener" class="loc-cell" title="<?=esc($loc)?>" style="color:var(--cyan);text-decoration:none;display:inline-block;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">📍 <?=esc($locShort)?><?=$loc!=='Not provided'&&strlen($loc)>50?'…':''?></a><?php else:?><span style="color:var(--muted);font-size:.74em;">—</span><?php endif;?></td>
            <td><div class="dev-cell" title="<?=esc($dev)?>"><?=esc($devShort)?></div></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$tm?></td>
            <td><?php if($is_super):?><button class="btn bd" onclick="act('del_call',<?=$c['id']?>,this,'crow<?=$c['id']?>')">🗑</button><?php endif;?></td>
          </tr><?php endforeach; if(empty($calls)):?><tr><td colspan="11" class="empty">কোনো call log নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ COMMUNITY ══ -->
    <div class="tab" id="tab-community">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">💬 Community Posts <span style="font-size:.65em;color:var(--muted);font-weight:400;">(<?=count($community_posts)?>)</span><button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar">
          <h3>Reviews &amp; Questions</h3>
          <input class="srch" placeholder="🔍 কন্টেন্ট / নাম..." oninput="ft('cmtb',this.value)">
        </div>
        <div class="bulk-bar" id="bulk-bar-community" <?php if($is_moderator) echo 'style="display:none!important;"'; ?>>
          <span>✅ <strong id="community-sel-count">0</strong> টি selected</span>
          <button class="bulk-del-btn" onclick="bulkDelete('community')">🗑 Delete Selected</button>
          <button class="bulk-cancel-btn" onclick="clearSelection('community')">✕ Cancel</button>
        </div>
        <div class="ow"><table>
          <thead><tr>
            <th class="cb-th"><input type="checkbox" id="cb-all-community" onchange="toggleAll('community',this.checked)"></th>
            <th>#</th><th>Type</th><th>User</th><th>Content</th><th>Rating</th><th>Replies</th><th>IP</th><th>Time</th><th>Del</th>
          </tr></thead>
          <tbody id="cmtb">
          <?php foreach($community_posts as $i=>$p):
            $pt=$p['type']==='review'?'⭐ Review':'❓ Question';
            $ratingStr=$p['rating'] ? str_repeat('★',(int)$p['rating']).str_repeat('☆',5-(int)$p['rating']) : '—';
            $ct=!empty($p['created_at'])?date('d M, h:i A',strtotime($p['created_at'])):'—';
          ?><tr id="crow<?=$p['id']?>">
            <td class="cb-td"><input type="checkbox" class="row-cb community-cb" value="<?=$p['id']?>" onchange="onRowCbChange('community')"></td>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td><?=$pt?></td>
            <td style="white-space:nowrap;"><?=esc($p['display_name']??'Anonymous')?></td>
            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?=esc($p['content'])?>"><?=esc(mb_substr($p['content'],0,80))?></td>
            <td><?=$ratingStr?></td>
            <td><?=(int)$p['reply_count']?></td>
            <td style="font-family:monospace;font-size:.74em;color:var(--muted);"><?=esc($p['ip_address']??'—')?></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$ct?></td>
            <td><?php if($is_super):?><button class="btn bd" onclick="act('del_community_post',<?=$p['id']?>,this,'crow<?=$p['id']?>')">🗑</button><?php endif;?></td>
          </tr><?php endforeach; if(empty($community_posts)):?><tr><td colspan="10" class="empty">কোনো Community Post নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div><!-- end tab-community -->

    <!-- ══ NOTIFICATIONS ══ -->
    <div class="tab" id="tab-notifications">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">🔔 Notifications<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>

      <!-- ══ Service Notification Manager ══ -->
      <div class="settings-section" style="border-color:rgba(16,185,129,.35);">
        <h4>🔔 Service Notification Manager — DB তে জমা থাকা Notifications</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:10px;">Donors এর app-এ service bell notification হিসেবে যায়। নিচে manage করুন।</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button onclick="clearAllServiceNotifs()" style="padding:8px 18px;background:rgba(239,68,68,.15);color:var(--red);border:1px solid rgba(239,68,68,.3);border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;">🗑 সব Service Notification মুছুন (DB)</button>
          <button onclick="clearReadServiceNotifs()" style="padding:8px 18px;background:rgba(107,114,128,.12);color:var(--muted);border:1px solid var(--bdr);border-radius:8px;font-size:.84em;cursor:pointer;">✓ শুধু Read গুলো মুছুন</button>
        </div>
        <div id="svcNotifClearResult" style="display:none;font-size:.82em;padding:8px 12px;border-radius:8px;margin-top:8px;"></div>
      </div>

      <!-- ══ FCM PUSH — সরাসরি Phone Notification Panel ══ -->
      <div class="settings-section" style="border-color:rgba(220,38,38,.35);">
        <h4>🔴 FCM Push — সরাসরি Phone Notification (Vibrate+Sound সহ)</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:12px;">এই push phone-এর notification panel-এ যাবে — app বন্ধ থাকলেও। Vibration ও sound সহ।<br><span style="color:var(--orange);font-size:.78em;">⚠️ Push message automatically Salaam দিয়ে শুরু হবে।</span></p>
        <div style="display:grid;gap:10px;max-width:580px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
              <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">🎯 Target</label>
              <select id="fcm_target" onchange="onFcmTargetChange()" style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
                <option value="broadcast">📢 সবাইকে (Broadcast)</option>
                <option value="active_donors">✅ শুধু Active Donors</option>
                <option value="blood_group">🩸 নির্দিষ্ট Blood Group</option>
                <option value="donor_ids">👤 Selected Donors (Donors tab)</option>
              </select>
            </div>
            <div id="fcm_bg_wrap" style="display:none;">
              <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Blood Group</label>
              <select id="fcm_blood_group" style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
              </select>
            </div>
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">📌 Notification Title</label>
            <input type="text" id="fcm_title" placeholder="যেমন: BloodArena — গুরুত্বপূর্ণ বিজ্ঞপ্তি" maxlength="100"
              style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">💬 Message Body</label>
            <textarea id="fcm_body" rows="4" maxlength="400"
              placeholder="Push message লিখুন... (আসসালামু আলাইকুম দিয়ে শুরু হবে automatically)"
              oninput="document.getElementById('fcm_body_len').textContent=this.value.length"
              style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;resize:vertical;box-sizing:border-box;"></textarea>
            <div style="font-size:.72em;color:var(--muted);text-align:right;"><span id="fcm_body_len">0</span>/400</div>
          </div>
          <button onclick="sendFcmPush()" style="padding:11px 22px;background:var(--red);color:#fff;border:none;border-radius:9px;font-weight:700;cursor:pointer;font-size:.9em;width:fit-content;">
            📲 Phone Push পাঠান (Vibrate+Sound)
          </button>
          <div id="fcm_push_result" style="display:none;font-size:.83em;padding:9px 12px;border-radius:8px;"></div>
        </div>
      </div>

      <!-- Bulk Notification -->
      <div class="settings-section">
        <h4>📢 Bulk Notification — সব Device</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:10px;">যেসব device এর কাছে আগে কোনো service notification গেছে বা push subscription আছে, সবাইকে একসাথে পাঠাবে।</p>
        <div style="display:grid;gap:8px;max-width:560px;">
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Notification Type</label>
            <select id="bulk_notif_type" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;width:100%;">
              <option value="info">ℹ️ Info (General)</option>
              <option value="warning">⚠️ Warning</option>
              <option value="location_on">📍 Location চালু করুন</option>
              <option value="notif_on">🔔 Notification চালু করুন</option>
            </select>
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Message</label>
            <textarea id="bulk_notif_msg" rows="3" placeholder="সব user কে যে message পাঠাবেন..." style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;resize:vertical;"></textarea>
          </div>
          <button onclick="sendBulkNotif()" style="padding:10px 20px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.88em;width:fit-content;">📢 Bulk Notification পাঠান</button>
          <div id="bulk_notif_result" style="display:none;font-size:.83em;padding:8px 12px;border-radius:8px;margin-top:4px;"></div>
        </div>
      </div>

      <!-- Auto Reminder Status -->
      <div class="settings-section">
        <h4>⏰ Auto Reminder — Not-Willing Donors (প্রতি ৩ দিন)</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:8px;">যেসব donor <strong>willing_to_donate = No</strong> কিন্তু device ID আছে, তাদের প্রতি ৩ দিন অন্তর স্বয়ংক্রিয়ভাবে একটি reminder notification পাঠানো হবে। Donor available হয়ে গেলে পরের poll cycle থেকে আর পাঠানো হবে না।</p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <span style="font-size:.82em;padding:6px 14px;border-radius:20px;background:rgba(16,185,129,.12);color:var(--green);border:1px solid rgba(16,185,129,.25);">✅ Auto-enabled — polling চালু থাকলে কাজ করে</span>
          <button onclick="triggerReminderNow()" style="padding:7px 16px;background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.3);border-radius:8px;font-size:.8em;font-weight:700;cursor:pointer;">🔔 এখনই Reminder পাঠান</button>
        </div>
        <div id="reminder_result" style="display:none;font-size:.82em;padding:8px 12px;border-radius:8px;margin-top:8px;"></div>
      </div>

      <!-- Specific Donor Notification -->
      <div class="settings-section">
        <h4>👤 Specific Donor — একজন Donor কে Notification</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:10px;">Donor এর phone number দিন (device ID আগে থেকে capture থাকতে হবে)।</p>
        <div style="display:grid;gap:8px;max-width:560px;">
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Donor Phone Number</label>
            <input type="tel" id="donor_notif_phone" placeholder="+8801XXXXXXXXX" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;width:100%;">
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Notification Type</label>
            <select id="donor_notif_type" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;width:100%;">
              <option value="info">ℹ️ Info</option>
              <option value="warning">⚠️ Warning</option>
              <option value="location_on">📍 Location চালু করুন</option>
              <option value="notif_on">🔔 Notification চালু করুন</option>
            </select>
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Message</label>
            <textarea id="donor_notif_msg" rows="3" placeholder="Donor কে যে message পাঠাবেন..." style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;resize:vertical;"></textarea>
          </div>
          <button onclick="sendDonorNotif()" style="padding:10px 20px;background:var(--green);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.88em;width:fit-content;">📤 Notification পাঠান</button>
          <div id="donor_notif_result" style="display:none;font-size:.83em;padding:8px 12px;border-radius:8px;margin-top:4px;"></div>
        </div>
      </div>
    </div>

    <!-- ══ INBOX (Messages from users) ══ -->
    <?php if($is_super):?>
    <div class="tab" id="tab-inbox">
      <div class="stitle">📬 User Messages (Inbox)
        <?php if($inbox_unread>0):?><span style="font-size:.6em;background:rgba(220,38,38,.15);color:var(--red);border-radius:20px;padding:2px 10px;margin-left:8px;font-weight:700;"><?=$inbox_unread?> unread</span><?php endif;?>
      </div>
      <div class="tbox">
        <div class="tbar" style="flex-wrap:wrap;gap:8px;">
          <h3>User Messages to Admin</h3>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button onclick="loadInbox('all')" id="ibf_all" class="inbox-filter-btn active" style="padding:5px 12px;border-radius:20px;background:rgba(59,130,246,.15);color:var(--blue);border:1px solid rgba(59,130,246,.3);font-size:.78em;cursor:pointer;">📬 All</button>
            <button onclick="loadInbox('unread')" id="ibf_unread" class="inbox-filter-btn" style="padding:5px 12px;border-radius:20px;background:var(--inp);color:var(--muted);border:1px solid var(--bdr);font-size:.78em;cursor:pointer;">🔴 Unread</button>
            <button onclick="loadInbox('replied')" id="ibf_replied" class="inbox-filter-btn" style="padding:5px 12px;border-radius:20px;background:var(--inp);color:var(--muted);border:1px solid var(--bdr);font-size:.78em;cursor:pointer;">✅ Replied</button>
            <button onclick="loadInbox(_inboxFilter)" style="padding:5px 12px;border-radius:20px;background:rgba(16,185,129,.12);color:var(--green);border:1px solid rgba(16,185,129,.25);font-size:.78em;cursor:pointer;">🔄 Refresh</button>
            <button onclick="clearInbox(_inboxFilter)" style="padding:5px 12px;border-radius:20px;background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.25);font-size:.78em;font-weight:700;cursor:pointer;">🗑 সব মুছুন</button>
          </div>
        </div>
        <div id="inboxList" style="padding:8px 0;">
          <div style="text-align:center;padding:40px;color:var(--muted);font-size:.85em;">⏳ লোড হচ্ছে...</div>
        </div>
      </div>
    </div>

    <?php endif; // end inbox super only ?>

    <!-- ══ SELF-REPORTED (OFF-PLATFORM) DONATIONS (Super Admin only) ══ -->
    <?php if($is_super):?>
    <div class="tab" id="tab-selfreports">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">🩸 Self-Reported Donations<button class="tab-refresh-btn" onclick="loadSelfReports()" title="Refresh">🔄</button></div>
      <div class="tbox">
        <div class="tbar" style="flex-wrap:wrap;gap:8px;">
          <h3>Off-platform (নিজে রিপোর্ট করা) রক্তদান</h3>
          <span style="font-size:.74em;color:var(--muted);">120-দিনের medical gate দিয়ে সীমিত। সন্দেহজনক হলে Revoke করুন (count −১)।</span>
        </div>
        <div id="selfReportsList" style="padding:8px 0;">
          <div style="text-align:center;padding:40px;color:var(--muted);font-size:.85em;">⏳ লোড হচ্ছে...</div>
        </div>
      </div>
    </div>
    <?php endif; // end self-reports super only ?>

    <!-- ══ MODERATOR MANAGEMENT (Super Admin only) ══ -->
    <?php if($is_super):?>
    <div class="tab" id="tab-moderators">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">👥 Moderator Management<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>

      <!-- Add Moderator -->
      <div class="settings-section">
        <h4>➕ নতুন Moderator / Admin যোগ করুন</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:12px;">Moderator: notification পাঠাতে পারবে। Delete করতে পারবে না, messages reply করতে পারবে না।</p>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;max-width:600px;margin-bottom:10px;">
          <input type="text" id="mod_uname" placeholder="Username (a-z 0-9 _-)" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
          <input type="password" id="mod_pass" placeholder="Password (min 8 chars)" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
          <select id="mod_role" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;">
            <option value="moderator">🛡️ Moderator</option>
            <option value="super_admin">👑 Super Admin</option>
          </select>
        </div>
        <button onclick="addModerator()" style="padding:9px 20px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85em;">➕ যোগ করুন</button>
        <div id="mod_result" style="display:none;font-size:.83em;padding:8px 12px;border-radius:8px;margin-top:8px;"></div>
      </div>

      <!-- List -->
      <div class="settings-section">
        <h4>📋 সব Users (<?php
          $mu_count=0;
          if($conn){ $mr=$conn->query("SELECT COUNT(*) c FROM admin_users"); if($mr) $mu_count=(int)$mr->fetch_assoc()['c']; }
          echo $mu_count;
        ?>)</h4>
        <div id="modList">
        <?php
        $mod_users=[];
        if($conn){ ensureAdminTables($conn); $mur=$conn->query("SELECT id,username,role,is_active,created_at FROM admin_users ORDER BY id DESC"); if($mur) while($r=$mur->fetch_assoc()) $mod_users[]=$r; }
        if(empty($mod_users)):?>
          <div style="text-align:center;padding:20px;color:var(--muted);font-size:.84em;">কোনো user নেই। উপরে যোগ করুন।</div>
        <?php else: foreach($mod_users as $mu):
          $mdate=!empty($mu['created_at'])?date('d M Y',strtotime($mu['created_at'])):'—';
          $mroleClr=$mu['role']==='super_admin'?'var(--red)':'var(--blue)';
          $mroleIcon=$mu['role']==='super_admin'?'👑':'🛡️';
        ?>
          <div class="token-row" id="modrow<?=$mu['id']?>" style="align-items:center;">
            <span style="font-weight:700;min-width:120px;"><?=esc($mu['username'])?></span>
            <span style="font-size:.8em;padding:2px 9px;border-radius:20px;background:rgba(255,255,255,.07);color:<?=$mroleClr?>;border:1px solid <?=$mroleClr?>33;"><?=$mroleIcon?> <?=esc($mu['role'])?></span>
            <span class="tok-date">📅 <?=$mdate?></span>
            <span style="font-size:.76em;padding:2px 8px;border-radius:20px;background:<?=$mu['is_active']?'rgba(16,185,129,.15)':'rgba(107,114,128,.15)'?>;color:<?=$mu['is_active']?'var(--green)':'var(--muted)'?>;"><?=$mu['is_active']?'✅ Active':'⛔ Inactive'?></span>
            <button class="tok-del" onclick="delModerator(<?=$mu['id']?>,this,'<?=esc($mu['username'])?>')">🗑 Remove</button>
          </div>
        <?php endforeach; endif;?>
        </div>
      </div>
    </div>
    <?php endif;?>

    <!-- ══ TOKEN MANAGER ══ -->
    <div class="tab" id="tab-tokens">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">🪙 Token Manager<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="settings-section">
        <h4>➕ নতুন Token তৈরি</h4>
        <p>API token তৈরি করুন। Token দিয়ে external apps data access করতে পারবে।</p>
        <div class="token-add-row">
          <input type="text" id="newTokenName" placeholder="Token এর নাম (যেমন: App v1, Website API...)">
          <button onclick="addToken()">➕ Token তৈরি করুন</button>
        </div>
        <div id="tokenResult" style="font-size:.84em;margin-top:6px;display:none;"></div>
      </div>
      <div class="settings-section">
        <h4>📋 সব Tokens (<?=count($token_list)?>)</h4>
        <div id="tokenList">
        <?php if(empty($token_list)):?>
          <div style="text-align:center;padding:20px;color:var(--muted);font-size:.84em;">কোনো token নেই।</div>
        <?php else: foreach($token_list as $tok):
          $tActive=(int)($tok['is_active']??1);
          $tDate=!empty($tok['created_at'])?date('d M Y',strtotime($tok['created_at'])):'—';
          $tLast=!empty($tok['last_used'])?date('d M, h:i A',strtotime($tok['last_used'])):'কখনো না';
        ?>
          <div class="token-row" id="tokrow<?=$tok['id']?>">
            <span class="tok-name"><?=esc($tok['token_name'])?></span>
            <span class="tok-val"><?=esc($tok['token_value'])?></span>
            <span class="tok-status <?=$tActive?'tok-active':'tok-inactive'?>" id="tok-status-<?=$tok['id']?>"><?=$tActive?'✅ Active':'⛔ Inactive'?></span>
            <span class="tok-date">📅 <?=$tDate?></span>
            <button class="tok-copy" onclick="copyToken('<?=esc($tok['token_value'])?>', this)">📋 Copy</button>
            <button class="tok-toggle <?=$tActive?'active-btn':'inactive-btn'?>" id="tok-toggle-<?=$tok['id']?>" onclick="toggleToken(<?=$tok['id']?>)">
              <?=$tActive?'⏸ Disable':'▶ Enable'?>
            </button>
            <?php if($is_super):?><button class="tok-del" onclick="delToken(<?=$tok['id']?>)">🗑</button><?php endif;?>
          </div>
        <?php endforeach; endif;?>
        </div>
      </div>
    </div>

    <!-- ══ ADVANCED SEARCH ══ -->
    <div class="tab" id="tab-advsearch">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">🔍 Advanced Donor Search<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="adv-grid">
          <div><label>নাম</label><input type="text" id="as_name" placeholder="যেকোনো নাম..."></div>
          <div><label>ফোন নম্বর</label><input type="text" id="as_phone" placeholder="+8801..."></div>
          <div><label>Blood Group</label>
            <select id="as_group">
              <option value="All">All Groups</option>
              <?php foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g) echo "<option>$g</option>"; ?>
            </select>
          </div>
          <div><label>Area / Location</label><input type="text" id="as_loc" placeholder="Mirpur, Dhaka..."></div>
          <div><label>Status</label>
            <select id="as_status">
              <option value="All">All Status</option>
              <option>Available</option><option>Not Available</option><option>Not Willing</option>
            </select>
          </div>
          <div><label>Badge Level</label>
            <select id="as_badge">
              <option value="All">All Badges</option>
              <option>New</option><option>Active</option><option>Hero</option><option>Legend</option>
            </select>
          </div>
          <div><label>Joined From</label><input type="date" id="as_from"></div>
          <div><label>Joined To</label><input type="date" id="as_to"></div>
        </div>
        <div class="adv-actions">
          <button class="adv-run" onclick="runAdvSearch()">🔍 Search</button>
          <button class="adv-clear" onclick="clearAdvSearch()">✕ Clear</button>
          <button class="adv-export" onclick="exportAdvCSV()" id="advExportBtn" style="display:none;">⬇️ CSV Export</button>
          <span class="adv-count" id="advCount"></span>
        </div>
        <div id="advResults">
          <div style="text-align:center;padding:40px;color:var(--muted);">
            <div style="font-size:2.5rem;margin-bottom:10px;">🔍</div>
            <p>উপরের filter দিয়ে donor খুঁজুন</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ AUDIT LOG ══ -->
    <div class="tab" id="tab-audit">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">📋 Audit Log<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <div class="tbox">
        <div class="tbar"><h3>Admin Activity (Latest 50)</h3></div>
        <div class="ow"><table>
          <thead><tr><th>#</th><th>Event</th><th>IP</th><th>Detail</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($audit_log as $i=>$a):
            $ev=strtolower($a['event']??'');
            $cls=(strpos($ev,'fail')!==false||strpos($ev,'bot')!==false)?'fail':((strpos($ev,'success')!==false||strpos($ev,'login_s')!==false)?'success':'');
            $tm=!empty($a['created_at'])?date('d M, h:i A',strtotime($a['created_at'])):'—';
          ?><tr>
            <td style="color:var(--muted);"><?=$i+1?></td>
            <td><span class="audit-ev <?=$cls?>"><?=esc($a['event']??'')?></span></td>
            <td style="font-family:monospace;font-size:.8em;"><?=esc($a['ip']??'')?></td>
            <td style="font-size:.79em;color:var(--muted);"><?=esc($a['detail']??'')?></td>
            <td style="font-size:.77em;color:var(--muted);white-space:nowrap;"><?=$tm?></td>
          </tr><?php endforeach; if(empty($audit_log)):?><tr><td colspan="5" class="empty">কোনো log নেই</td></tr><?php endif;?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ══ SETTINGS ══ -->
    <div class="tab" id="tab-settings">
      <div class="stitle" style="display:flex;align-items:center;justify-content:space-between;">⚙️ Settings<button class="tab-refresh-btn" onclick="location.reload()" title="Page reload করুন">🔄</button></div>
      <?php if($is_moderator):?>
      <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);border-radius:12px;padding:14px 18px;margin-bottom:16px;font-size:.85em;color:var(--blue);">
        🛡️ আপনি Moderator হিসেবে logged in। Settings পরিবর্তন শুধুমাত্র Super Admin করতে পারবে।
      </div>
      <?php endif;?>

      <!-- Browser Notifications (available to all roles) -->
      <div class="settings-section">
        <h4>🔔 Admin Browser Notifications</h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:12px;">নতুন <strong>Inbox message</strong> এলে browser notification আসবে — tab background এ থাকলেও কাজ করবে।</p>
        <div id="adminNotifStatus" style="margin-bottom:12px;padding:10px 14px;border-radius:10px;font-size:.83em;background:rgba(107,114,128,.1);border:1px solid var(--bdr);">
          ⏳ Notification status check করা হচ্ছে...
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <button id="adminNotifEnableBtn" onclick="adminEnableNotif()" style="padding:8px 18px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;">🔔 Enable Notifications</button>
          <button id="adminNotifTestBtn" onclick="adminTestNotif()" style="padding:8px 14px;background:rgba(16,185,129,.15);color:var(--green);border:1px solid rgba(16,185,129,.3);border-radius:8px;font-size:.84em;cursor:pointer;display:none;">🧪 Test</button>
        </div>
        <div style="margin-top:12px;display:flex;align-items:center;gap:10px;">
          <span style="font-size:.82em;color:var(--muted);">Auto-polling</span>
          <button class="toggle-btn" id="adminPollToggle" onclick="toggleAdminPoll(this)" title="Polling on/off"></button>
          <span style="font-size:.76em;color:var(--muted);" id="adminPollStatus">প্রতি ৩০s এ check করে</span>
        </div>
      </div>

      <!-- IP Whitelist -->
      <div class="settings-section" <?php if($is_moderator) echo 'style="opacity:.55;pointer-events:none;"'; ?>>
        <h4>🔒 IP Whitelist <?php if($is_moderator) echo '<span style="font-size:.72em;color:var(--muted);">(Super Admin only)</span>'; ?></h4>
        <p>শুধুমাত্র whitelist করা IP গুলো এই admin panel access করতে পারবে। <strong style="color:var(--red);">⚠️ Enable করার আগে অবশ্যই নিজের IP add করুন।</strong></p>
        <div class="ip-mine">
          আপনার বর্তমান IP: <code><?=esc($currentIP)?></code>
          <button onclick="addMyIP()" style="margin-left:10px;padding:3px 10px;background:rgba(59,130,246,.15);color:var(--blue);border:1px solid rgba(59,130,246,.3);border-radius:6px;font-size:.8em;cursor:pointer;">➕ আমার IP Add করুন</button>
        </div>
        <div class="toggle-row">
          <span>IP Whitelist <?=$ip_whitelist_enabled?'<span style="color:var(--green);font-size:.8em;margin-left:6px;">● Enabled</span>':'<span style="color:var(--muted);font-size:.8em;margin-left:6px;">● Disabled</span>'?></span>
          <button class="toggle-btn <?=$ip_whitelist_enabled?'on':''?>" id="ipWlToggleBtn" onclick="toggleIpWhitelist(this)"></button>
        </div>
        <div class="ip-add-row">
          <input type="text" id="newIpAddr" placeholder="IP Address (যেমন: 192.168.1.1)">
          <input type="text" id="newIpLabel" placeholder="Label (যেমন: Office PC)" style="max-width:200px;">
          <button onclick="addIp()">➕ IP Add করুন</button>
        </div>
        <div id="ipList">
          <?php if(empty($ip_list)):?>
            <div style="text-align:center;padding:16px;color:var(--muted);font-size:.84em;">কোনো IP whitelist করা নেই।</div>
          <?php else: foreach($ip_list as $ip): ?>
            <div class="ip-tag" id="iptag<?=$ip['id']?>">
              <span class="ip-addr"><?=esc($ip['ip'])?></span>
              <span class="ip-label"><?=esc($ip['label']??'')?></span>
              <span class="ip-badge <?=($ip['is_active']??1)?'active':'inactive'?>"><?=($ip['is_active']??1)?'✅ Active':'⛔ Off'?></span>
              <?php if($is_super):?><button class="ip-del-btn" onclick="delIp(<?=$ip['id']?>)">🗑 Remove</button><?php endif;?>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- ══ SITE CONFIG EDITOR (UI-based config — super admin only) ══ -->
      <?php if($is_super):?>
      <div class="settings-section" id="cfgSection">
        <h4>🎛️ Site Config <span style="font-size:.72em;color:var(--muted);">(brand, contact, social, theme, firebase, bots)</span></h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:12px;">config.php না খুলেই এখান থেকে সব setting বদলান। পরিবর্তন <strong>সাথে সাথে</strong> কার্যকর হয় এবং <code>config_overrides.json</code>-এ নিরাপদে সংরক্ষিত হয় (মূল config.php কখনো overwrite হয় না)।</p>

        <!-- Lock screen: editor password gate -->
        <div id="cfgLock">
          <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:16px 18px;max-width:420px;">
            <div style="font-size:.9em;font-weight:700;margin-bottom:10px;">🔒 Site Config খুলতে password দিন</div>
            <input type="password" id="cfgPass" placeholder="Config password" autocomplete="off"
              style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);margin-bottom:10px;"
              onkeydown="if(event.key==='Enter')cfgUnlock()">
            <button onclick="cfgUnlock()" style="padding:9px 18px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;">🔓 Unlock</button>
            <div id="cfgUnlockMsg" style="font-size:.82em;margin-top:8px;"></div>
            <div style="font-size:.74em;color:var(--muted);margin-top:8px;">ℹ️ প্রথমবার default password: <code>alif2005@A</code> — unlock করার পর নিচ থেকে বদলে নিন।</div>
          </div>
        </div>

        <!-- Editor body (hidden until unlocked) -->
        <div id="cfgBody" style="display:none;">
          <div id="cfgForm" style="display:grid;gap:18px;"></div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;align-items:center;">
            <button onclick="cfgSave()" id="cfgSaveBtn" style="padding:10px 22px;background:var(--green);color:#fff;border:none;border-radius:9px;font-weight:800;cursor:pointer;">💾 Save Config</button>
            <button onclick="cfgReset()" style="padding:10px 18px;background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.3);border-radius:9px;font-weight:700;cursor:pointer;">♻️ Reset to defaults</button>
            <button onclick="cfgLockNow()" style="padding:10px 16px;background:rgba(107,114,128,.15);color:var(--muted);border:1px solid var(--bdr);border-radius:9px;cursor:pointer;">🔒 Lock</button>
            <span id="cfgSaveMsg" style="font-size:.85em;"></span>
          </div>

          <!-- Change editor password -->
          <div style="margin-top:22px;padding-top:18px;border-top:1px solid var(--bdr);max-width:420px;">
            <div style="font-size:.88em;font-weight:700;margin-bottom:10px;">🔑 Config password পরিবর্তন</div>
            <input type="password" id="cfgCurPass" placeholder="বর্তমান config password" autocomplete="off" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);margin-bottom:8px;">
            <input type="password" id="cfgNewPass" placeholder="নতুন password (min 8)" autocomplete="off" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);margin-bottom:8px;">
            <input type="password" id="cfgCnfPass" placeholder="নতুন password নিশ্চিত করুন" autocomplete="off" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);margin-bottom:10px;">
            <button onclick="cfgChangePass()" style="padding:9px 18px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;">🔑 পরিবর্তন করুন</button>
            <div id="cfgPassMsg" style="font-size:.82em;margin-top:8px;"></div>
          </div>
        </div>
      </div>
      <?php endif;?>

      <!-- ══ SMS GATEWAY SETTINGS ══ -->
      <?php if($is_super):?>
      <div class="settings-section" id="smsSection">
        <h4>📱 SMS Gateway Settings <span style="font-size:.72em;color:var(--muted);">(env.sms)</span></h4>
        <p style="font-size:.82em;color:var(--muted);margin-bottom:12px;">SMS gateway credentials এবং endpoint <code>.env.sms</code> ফাইল থেকে read/write করা হয়। পরিবর্তন <strong>সাথে সাথে</strong> কার্যকর হয়।</p>
        <div id="smsForm" style="display:grid;gap:12px;max-width:520px;">
          <div>
            <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">API Endpoint URL</label>
            <input type="url" id="sms_endpoint" placeholder="https://api.sms-gate.app/api/3rdparty/v1/message" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;font-size:.85em;">
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Auth Type</label>
            <select id="sms_auth_type" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-size:.85em;">
              <option value="basic">Basic Auth (SMSGate)</option>
              <option value="bearer">Bearer Token</option>
              <option value="custom">Custom Header</option>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
              <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Username / Key</label>
              <input type="text" id="sms_username" placeholder="your_username" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;font-size:.85em;">
            </div>
            <div>
              <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Password / Token</label>
              <div style="position:relative;">
                <input type="password" id="sms_password" placeholder="••••••" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;font-size:.85em;padding-right:36px;">
                <span onclick="smsTogglePass()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1.1rem;user-select:none;">👁</span>
              </div>
            </div>
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Device ID <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
            <input type="text" id="sms_device_id" placeholder="device-id" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;font-size:.85em;">
          </div>
          <div>
            <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Server Address <span style="font-weight:400;color:var(--muted);">(optional, device ID resolve)</span></label>
            <input type="text" id="sms_server_address" placeholder="api.sms-gate.app:443" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;font-size:.85em;">
          </div>
          <div class="toggle-row" style="margin-bottom:0;">
            <span>SMS Enabled <span id="smsEnabledBadge" style="font-size:.78em;"></span></span>
            <button class="toggle-btn" id="smsEnabledToggle" onclick="smsToggleEnabled(this)"></button>
          </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;align-items:center;">
          <button onclick="smsSave()" id="smsSaveBtn" style="padding:10px 22px;background:var(--green);color:#fff;border:none;border-radius:9px;font-weight:800;cursor:pointer;">💾 Save</button>
          <button onclick="smsTest()" id="smsTestBtn" style="padding:10px 18px;background:rgba(59,130,246,.12);color:var(--blue);border:1px solid rgba(59,130,246,.3);border-radius:9px;font-weight:700;cursor:pointer;">📱 Test SMS</button>
          <span id="smsMsg" style="font-size:.85em;"></span>
        </div>
        <div id="smsTestArea" style="display:none;margin-top:12px;padding:12px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2);max-width:520px;">
          <label style="font-size:.78em;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;">Test phone number</label>
          <div style="display:flex;gap:8px;">
            <input type="tel" id="smsTestPhone" placeholder="<?=defined('CONTACT_PHONE')?htmlspecialchars(CONTACT_PHONE):'+8801XXXXXXXXX'?>" style="flex:1;padding:9px 12px;border-radius:8px;border:1px solid var(--bdr);background:var(--inp);color:var(--text);font-family:monospace;font-size:.85em;">
            <button id="smsDoTestBtn" onclick="smsSendTest()" style="padding:9px 16px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;">পাঠান</button>
          </div>
          <div id="smsTestResult" style="font-size:.82em;margin-top:6px;"></div>
        </div>
      </div>
      <?php endif;?>

      <!-- Password Change -->
      <?php if($is_super):?>
      <div class="settings-section">
        <h4>🔑 Admin Password পরিবর্তন</h4>
        <p>এখান থেকে admin panel এর password পরিবর্তন করুন। নতুন password কমপক্ষে ৮ অক্ষরের হতে হবে।</p>
        <div class="pw-form" style="max-width:400px;">
          <div>
            <label>বর্তমান Password</label>
            <input type="password" id="pw_current" placeholder="বর্তমান password লিখুন" autocomplete="current-password">
          </div>
          <div>
            <label>নতুন Password</label>
            <input type="password" id="pw_new" placeholder="নতুন password (min 8 chars)" autocomplete="new-password">
          </div>
          <div>
            <label>নতুন Password নিশ্চিত করুন</label>
            <input type="password" id="pw_confirm" placeholder="আবার নতুন password লিখুন" autocomplete="new-password">
          </div>
          <button class="pw-save-btn" id="pwSaveBtn" onclick="changePassword()">🔑 Password পরিবর্তন করুন</button>
          <div class="pw-result" id="pwResult"></div>
        </div>
      </div>
      <?php endif;?>
    </div>

    <div class="admin-footer"><span>🩸 © 2026 Siam Innovatives — All Rights Reserved.</span></div>

  </main>
</div><!-- /.layout -->

<!-- ══ EDIT DONOR MODAL ══ -->
<div class="modal-overlay" id="editDonorModal" style="display:none;" onclick="if(event.target===this)closeEditModal()">
  <div class="edit-modal">
    <h3>✏️ Edit Donor Info</h3>
    <input type="hidden" id="edit_donor_id" value="">
    <input type="hidden" id="edit_reg_geo" value="">
    <div class="ef-row">
      <div>
        <label class="ef-label">Full Name</label>
        <input type="text" class="ef-input" id="edit_name" placeholder="নাম লিখুন">
      </div>
      <div>
        <label class="ef-label">Phone</label>
        <input type="text" class="ef-input" id="edit_phone" placeholder="+880XXXXXXXXXX">
      </div>
    </div>
    <div class="ef-row">
      <div>
        <label class="ef-label">Blood Group</label>
        <select class="ef-input" id="edit_blood_group">
          <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
          <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
        </select>
      </div>
      <div>
        <label class="ef-label">Total Donations</label>
        <input type="number" class="ef-input" id="edit_total_donations" min="0" max="999" placeholder="0" oninput="updateEditBadgePreview(this.value)">
        <div id="editBadgePreview" style="margin-top:5px;font-size:.78em;font-weight:700;color:var(--muted);"></div>
      </div>
    </div>
    <div class="ef-row full">
      <div>
        <label class="ef-label">Location</label>
        <div style="display:flex;gap:6px;align-items:center;">
          <input type="text" class="ef-input" id="edit_location" placeholder="এলাকা / ঠিকানা" style="margin:0;flex:1;">
          <button class="ef-map-btn" onclick="openEditMapPicker()" title="Map থেকে বেছে নিন">🗺️</button>
        </div>
        <div class="ef-geo-status" id="editGeoStatus">📍 Geo location saved</div>
      </div>
    </div>
    <div class="ef-row">
      <div>
        <label class="ef-label">Last Donation Date</label>
        <div style="display:flex;gap:6px;align-items:center;">
          <input type="date" class="ef-input" id="edit_last_date" style="flex:1;" onchange="syncEditDate(this.value)">
          <button onclick="clearEditDate()" style="padding:7px 10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,38,.3);color:var(--red);border-radius:8px;cursor:pointer;font-size:.8em;flex-shrink:0;white-space:nowrap;">✕ Never</button>
        </div>
        <input type="hidden" id="edit_last_donation" value="no">
        <div style="font-size:.7em;color:var(--muted);margin-top:2px;" id="editDateDisplay">Never donated</div>
      </div>
      <div>
        <label class="ef-label">রক্ত দিতে ইচ্ছুক?</label>
        <div class="ef-toggle">
          <button id="ef_yes_btn" class="on-yes" onclick="setEditWilling('yes')">✅ হ্যাঁ</button>
          <button id="ef_no_btn"  onclick="setEditWilling('no')">⛔ না</button>
        </div>
        <input type="hidden" id="edit_willing" value="yes">
      </div>
    </div>
    <button class="ef-save" id="editSaveBtn" onclick="saveEditDonor()">💾 Save Changes</button>
    <button class="ef-cancel" onclick="closeEditModal()">Cancel</button>
  </div>
</div>

<script>
const CSRF = '<?=esc($CSRF)?>';
const IDLE_LIMIT = <?=SESSION_IDLE_LIMIT?>;
const IS_SUPER = <?=$is_super?'true':'false'?>;
const IS_MODERATOR = <?=$is_moderator?'true':'false'?>;
let lastActivity = Date.now();
let _advRows = [];

// Client-side guard — belt + suspenders on top of server-side check
function guardSuper(label) {
    if (!IS_SUPER) {
        alert('🚫 ' + (label||'এই কাজটি') + ' শুধুমাত্র Super Admin করতে পারবে।');
        return false;
    }
    return true;
}

// ── Tab switch ──────────────────────────────────────────
function go(name, el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
    document.querySelectorAll('.ni, .mt').forEach(n => n.classList.remove('on'));
    const t = document.getElementById('tab-' + name);
    if(t) t.classList.add('on');
    if(el) el.classList.add('on');
    document.querySelectorAll(`.ni[onclick*="'${name}'"], .mt[onclick*="'${name}'"]`).forEach(n => n.classList.add('on'));
}

// ── Table text filter ───────────────────────────────────
function ft(id, q) {
    const tb = document.getElementById(id); if(!tb) return;
    q = q.toLowerCase();
    tb.querySelectorAll('tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// ── Toast notification ──────────────────────────────────
function showToast(msg, color='#10b981'){
    const t=document.createElement('div');
    t.style.cssText=`position:fixed;top:60px;right:16px;background:${color};color:#fff;padding:10px 18px;border-radius:10px;font-weight:700;font-size:.88em;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,.4);`;
    t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),3000);
}

// ── CRUD AJAX ───────────────────────────────────────────
function act(action, id, btn, rowId) {
    const msgs = {
        del_donor:   '⚠️ এই Donor permanently delete হবে। নিশ্চিত?',
        del_req:     '🗑 এই Request permanently delete করবেন?',
        del_report:  '🗑 এই Report permanently delete করবেন?',
        del_call:    '🗑 এই Call Log permanently delete করবেন?',
        del_community_post: '🗑 এই Community Post permanently delete করবেন?',
        fulfill_req: '✅ Fulfilled mark করবেন?'
    };
    if(!confirm(msgs[action]||'Delete করবেন?')) return;
    btn.disabled=true; btn.textContent='⏳';
    const fd=new FormData();
    fd.append('act',action); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(d=>{
        if(d.ok){
            const row=document.getElementById(rowId)||btn.closest('tr');
            if(row){row.style.opacity='.3';row.style.transition='opacity .3s';setTimeout(()=>row.remove(),300);}
        } else {
            btn.disabled=false;
            btn.textContent=action.includes('del')?'🗑':'✅';
            alert('❌ '+(d.msg||'Action failed'));
        }
    }).catch(e=>{btn.disabled=false;btn.textContent='❌';alert('Network error: '+e.message);});
}

// ── Checkbox / Bulk Delete ──────────────────────────────
function toggleAll(table, checked) {
    document.querySelectorAll(`.${table}-cb`).forEach(cb => cb.checked = checked);
    updateBulkBar(table);
}

function onRowCbChange(table){
    updateBulkBar(table);
    const all = document.querySelectorAll(`.${table}-cb`);
    const checked = document.querySelectorAll(`.${table}-cb:checked`);
    const masterCb = document.getElementById(`cb-all-${table}`);
    if(masterCb) masterCb.indeterminate = checked.length > 0 && checked.length < all.length;
    if(masterCb) masterCb.checked = all.length > 0 && checked.length === all.length;
}

function updateBulkBar(table){
    const count = document.querySelectorAll(`.${table}-cb:checked`).length;
    const bar = document.getElementById(`bulk-bar-${table}`);
    const countEl = document.getElementById(`${table}-sel-count`);
    if(bar) bar.classList.toggle('show', count > 0);
    if(countEl) countEl.textContent = count;
}

function clearSelection(table){
    document.querySelectorAll(`.${table}-cb`).forEach(cb => cb.checked = false);
    const masterCb = document.getElementById(`cb-all-${table}`);
    if(masterCb){ masterCb.checked = false; masterCb.indeterminate = false; }
    updateBulkBar(table);
}

function bulkDelete(table){
    if(!guardSuper("Bulk Delete")) return;
    const checked = document.querySelectorAll(`.${table}-cb:checked`);
    if(!checked.length){ alert('কোনো row select করা হয়নি।'); return; }
    const ids = Array.from(checked).map(cb => cb.value);
    if(!confirm(`⚠️ ${ids.length} টি item permanently delete হবে। নিশ্চিত?`)) return;

    const fd = new FormData();
    fd.append('act','del_multiple'); fd.append('table',table);
    fd.append('ids',ids.join(',')); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            // Remove rows
            const rowPrefixes = {donors:'drow',requests:'rr',reports:'pr',calls:'crow'};
            ids.forEach(id=>{
                const row = document.getElementById((rowPrefixes[table]||table)+id);
                if(row){ row.style.opacity='.2'; setTimeout(()=>row.remove(),250); }
            });
            clearSelection(table);
            showToast(`✅ ${d.deleted} টি item delete হয়েছে।`);
        } else { alert('❌ '+(d.msg||'Bulk delete failed')); }
    }).catch(e=>alert('Network error: '+e.message));
}

// ── INBOX ─────────────────────────────────────────────────
var _inboxFilter='all';
// ── Self-Reported (off-platform) donations: list + revoke (super admin) ──
function _sresc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function loadSelfReports(){
    var list=document.getElementById('selfReportsList');
    if(!list) return;
    list.innerHTML='<div style="text-align:center;padding:30px;color:var(--muted);">⏳ লোড হচ্ছে...</div>';
    var fd=new FormData(); fd.append('act','get_self_reports'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(!d.ok){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--red);">❌ Load করতে সমস্যা।</div>'; return; }
        if(!d.rows||!d.rows.length){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--muted);font-size:.85em;">📭 কোনো self-reported রক্তদান নেই</div>'; return; }
        var h='<div class="ow"><table><thead><tr><th>#</th><th>নাম</th><th>ফোন</th><th>তারিখ</th><th>স্থান</th><th>IP</th><th>যোগ হয়েছে</th><th>মোট</th><th></th></tr></thead><tbody>';
        d.rows.forEach(function(r,i){
            var added = r.ts ? new Date(r.ts*1000).toLocaleString() : '—';
            h+='<tr>'+
               '<td>'+(i+1)+'</td>'+
               '<td>'+_sresc(r.name||'—')+'</td>'+
               '<td>'+_sresc(r.phone||'—')+'</td>'+
               '<td>'+_sresc(r.donation_date||'—')+'</td>'+
               '<td>'+_sresc(r.note||'—')+'</td>'+
               '<td style="font-size:.8em;color:var(--muted);">'+_sresc(r.reported_ip||'—')+'</td>'+
               '<td style="font-size:.8em;color:var(--muted);">'+_sresc(added)+'</td>'+
               '<td style="text-align:center;">'+(r.total_donations!=null?parseInt(r.total_donations):'—')+'</td>'+
               '<td><button onclick="revokeSelfReport('+parseInt(r.id)+',this)" style="padding:4px 10px;border-radius:8px;background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.3);font-size:.76em;font-weight:700;cursor:pointer;white-space:nowrap;">Revoke −1</button></td>'+
               '</tr>';
        });
        h+='</tbody></table></div>';
        list.innerHTML=h;
    }).catch(function(){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--red);">❌ Network error।</div>'; });
}
function revokeSelfReport(id, btn){
    if(!confirm('এই self-reported রক্তদানটি বাতিল করবেন? Donor-এর donation count −১ হবে।')) return;
    if(btn){ btn.disabled=true; btn.textContent='⏳'; }
    var fd=new FormData(); fd.append('act','revoke_self_report'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        showToast(d.msg || (d.ok?'✅ Done':'❌ ব্যর্থ'), d.ok?'#10b981':'#ef4444');
        if(d.ok) loadSelfReports();
        else if(btn){ btn.disabled=false; btn.textContent='Revoke −1'; }
    }).catch(function(){ if(btn){ btn.disabled=false; btn.textContent='Revoke −1'; } showToast('❌ Network error','#ef4444'); });
}

function loadInbox(filter){
    _inboxFilter=filter||'all';
    ['all','unread','replied'].forEach(function(f){
        var btn=document.getElementById('ibf_'+f);
        if(!btn) return;
        if(f===_inboxFilter){
            btn.style.background='rgba(59,130,246,.2)'; btn.style.color='var(--blue)'; btn.style.borderColor='rgba(59,130,246,.5)';
        } else {
            btn.style.background='var(--inp)'; btn.style.color='var(--muted)'; btn.style.borderColor='var(--bdr)';
        }
    });
    const list=document.getElementById('inboxList');
    if(!list) return;
    list.innerHTML='<div style="text-align:center;padding:30px;color:var(--muted);">⏳ লোড হচ্ছে...</div>';
    const fd=new FormData(); fd.append('act','get_inbox'); fd.append('filter',_inboxFilter); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(!d.ok){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--red);">❌ Load করতে সমস্যা।</div>'; return; }
        if(!d.rows.length){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--muted);font-size:.85em;">📭 কোনো message নেই</div>'; return; }

        list.innerHTML = d.rows.map(function(m){
            const isUnread=!m.is_read && !m.admin_reply;
            const bdr=isUnread?'rgba(59,130,246,.4)':'var(--bdr)';
            const bg=isUnread?'rgba(59,130,246,.05)':'var(--card)';
            const tm=m.created_at||'—';
            const replyHtml=m.admin_reply
                ?`<div style="margin-top:8px;padding:8px 12px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);border-radius:8px;font-size:.8em;color:var(--green);">✅ Reply: ${escHtml(m.admin_reply)}<span style="color:var(--muted);margin-left:8px;font-size:.85em;">${m.replied_at||''}</span></div>`
                :'';
            const replyFormHtml=!m.admin_reply?`
                <div style="margin-top:10px;display:flex;gap:6px;" id="replyform_${m.id}">
                  <textarea id="reply_${m.id}" rows="2" placeholder="Reply লিখুন..." style="flex:1;padding:8px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.83em;resize:none;font-family:sans-serif;"></textarea>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <button onclick="sendInboxReply(${m.id})" style="padding:6px 12px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:.78em;font-weight:700;cursor:pointer;white-space:nowrap;">📤 Reply</button>
                    <button onclick="markInboxRead(${m.id},this)" style="padding:6px 12px;background:var(--inp);color:var(--muted);border:1px solid var(--bdr);border-radius:8px;font-size:.76em;cursor:pointer;white-space:nowrap;">✓ Read</button>
                  </div>
                </div>`:'';
            return `<div id="inbox_${m.id}" style="margin-bottom:10px;padding:14px 16px;background:${bg};border:1px solid ${bdr};border-radius:12px;">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <div>
                  <span style="font-weight:700;font-size:.9em;">${escHtml(m.sender_name)}</span>
                  <span style="font-family:monospace;font-size:.78em;color:var(--muted);margin-left:8px;">${escHtml(m.sender_phone)}</span>
                  ${isUnread?'<span style="margin-left:6px;font-size:.7em;background:rgba(59,130,246,.2);color:var(--blue);padding:1px 7px;border-radius:20px;font-weight:700;">NEW</span>':''}
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span style="font-size:.74em;color:var(--muted);white-space:nowrap;">${tm}</span>
                  <button onclick="delInboxMsg(${m.id})" title="Delete" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red);border-radius:8px;padding:3px 8px;font-size:.76em;cursor:pointer;min-height:unset;box-shadow:none;margin:0;">🗑</button>
                </div>
              </div>
              <div style="margin-top:8px;font-size:.85em;color:var(--text);line-height:1.55;white-space:pre-wrap;">${escHtml(m.message)}</div>
              ${replyHtml}
              ${replyFormHtml}
            </div>`;
        }).join('');

        // Update sidebar badge
        const sideEl=document.querySelector('.ni[onclick*="inbox"] .cnt');
        if(d.unread>0){
            if(sideEl) sideEl.textContent=d.unread;
        } else {
            if(sideEl) sideEl.remove();
        }
    }).catch(function(){ list.innerHTML='<div style="text-align:center;padding:30px;color:var(--red);">❌ Network error।</div>'; });
}

function sendInboxReply(msgId){
    const txt=(document.getElementById('reply_'+msgId)||{}).value||'';
    if(!txt.trim()){ alert('Reply লিখুন।'); return; }
    const fd=new FormData(); fd.append('act','reply_inbox_msg'); fd.append('id',msgId); fd.append('reply',txt.trim()); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){ showToast(d.msg,'#10b981'); loadInbox(_inboxFilter); }
        else { alert('❌ '+(d.msg||'Error')); }
    }).catch(()=>alert('Network error'));
}

function delInboxMsg(id){
    if(!confirm('🗑 এই message টি মুছে ফেলবেন?')) return;
    const row=document.getElementById('inbox_'+id);
    if(row){ row.style.opacity='0.4'; row.style.pointerEvents='none'; }
    const fd=new FormData(); fd.append('act','del_inbox_msg'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            if(row){ row.style.transition='all .3s'; row.style.maxHeight='0'; row.style.overflow='hidden'; row.style.margin='0'; row.style.padding='0';
                setTimeout(()=>row.remove(),300); }
            showToast('✅ Message মুছে ফেলা হয়েছে।');
        } else {
            if(row){ row.style.opacity='1'; row.style.pointerEvents=''; }
            alert('❌ '+(d.msg||'Error'));
        }
    }).catch(()=>{ if(row){row.style.opacity='1';row.style.pointerEvents='';} alert('Network error'); });
}

function clearInbox(filter){
    const filterLabel = filter==='all'?'সব message':'এই filter এর সব message';
    if(!confirm('🗑 '+filterLabel+' মুছে ফেলবেন? এটি undo করা যাবে না।')) return;
    const fd=new FormData(); fd.append('act','clear_inbox'); fd.append('filter',filter||'all'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){ showToast(d.msg,'#10b981'); loadInbox(_inboxFilter); }
        else { alert('❌ '+(d.msg||'Error')); }
    }).catch(()=>alert('Network error'));
}

function markInboxRead(msgId,btn){
    const fd=new FormData(); fd.append('act','mark_inbox_read'); fd.append('id',msgId); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const row=document.getElementById('inbox_'+msgId);
            if(row){ row.style.border='1px solid var(--bdr)'; row.style.background='var(--card)'; }
            const form=document.getElementById('replyform_'+msgId);
            if(form) form.remove();
            if(btn) btn.remove();
        }
    }).catch(()=>{});
}

// ── MODERATOR MANAGEMENT ──────────────────────────────────
function addModerator(){
    const uname=(document.getElementById('mod_uname').value||'').trim();
    const pass=(document.getElementById('mod_pass').value||'').trim();
    const role=document.getElementById('mod_role').value;
    const res=document.getElementById('mod_result');
    if(!uname||!pass){ alert('Username ও Password দিন।'); return; }
    const fd=new FormData(); fd.append('act','add_moderator'); fd.append('uname',uname); fd.append('pass',pass); fd.append('role',role); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.display='block';
        if(d.ok){
            res.style.background='rgba(16,185,129,.12)'; res.style.color='var(--green)';
            res.textContent='✅ '+uname+' ('+role+') যোগ করা হয়েছে।';
            document.getElementById('mod_uname').value=''; document.getElementById('mod_pass').value='';
            // Add row to list
            const list=document.getElementById('modList');
            const empty=list.querySelector('div[style*="text-align:center"]');
            if(empty) empty.remove();
            const roleClr=d.role==='super_admin'?'var(--red)':'var(--blue)';
            const roleIcon=d.role==='super_admin'?'👑':'🛡️';
            const row=document.createElement('div');
            row.className='token-row'; row.id='modrow'+d.id;
            row.style.alignItems='center';
            row.innerHTML=`<span style="font-weight:700;min-width:120px;">${escHtml(d.username)}</span>
              <span style="font-size:.8em;padding:2px 9px;border-radius:20px;background:rgba(255,255,255,.07);color:${roleClr};border:1px solid ${roleClr}33;">${roleIcon} ${escHtml(d.role)}</span>
              <span class="tok-date">📅 ${d.created_at||'—'}</span>
              <span style="font-size:.76em;padding:2px 8px;border-radius:20px;background:rgba(16,185,129,.15);color:var(--green);">✅ Active</span>
              <button class="tok-del" onclick="delModerator(${d.id},this,'${escHtml(d.username)}')">🗑 Remove</button>`;
            list.prepend(row);
            showToast('✅ '+d.username+' যোগ হয়েছে!');
        } else {
            res.style.background='rgba(239,68,68,.12)'; res.style.color='var(--red)';
            res.textContent='❌ '+(d.msg||'Error');
        }
    }).catch(()=>{ res.style.display='block'; res.textContent='❌ Network error'; });
}

function delModerator(id,btn,uname){
    if(!confirm('🗑 "'+uname+'" কে remove করবেন?')) return;
    const fd=new FormData(); fd.append('act','del_moderator'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){ const row=document.getElementById('modrow'+id); if(row) row.remove(); showToast('✅ Remove হয়েছে।'); }
        else { alert('❌ '+(d.msg||'Error')); }
    }).catch(()=>alert('Network error'));
}

// ── Auto-load inbox when tab opened ──────────────────────
(function(){
    const orig=window.go;
    window.go=function(tab,el){
        orig(tab,el);
        if(tab==='inbox' && IS_SUPER) loadInbox(_inboxFilter);
    };
})();

// ── NOTIFICATIONS ─────────────────────────────────────────
function triggerReminderNow(){
    var btn = document.querySelector('[onclick="triggerReminderNow()"]');
    var res = document.getElementById('reminder_result');
    if(btn){ btn.disabled=true; btn.textContent='⏳ পাঠানো হচ্ছে...'; }
    res.style.display='block'; res.textContent='⏳ পাঠানো হচ্ছে...'; res.style.color='var(--muted)'; res.style.background='var(--inp)';
    var fd=new FormData(); fd.append('act','run_auto_reminder'); fd.append('force','1'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(function(d){
        if(btn){ btn.disabled=false; btn.textContent='🔔 এখনই Reminder পাঠান'; }
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        if(d.skipped){
            res.style.background='rgba(245,158,11,.1)'; res.style.color='var(--orange)';
            res.textContent=(d.msg||'Too soon')+(d.last_run?' শেষ run: '+d.last_run:'');
        } else {
            var txt = d.msg||(d.ok?'Done':'Error');
            res.textContent = txt;
            if(d.ok && d.sent>0) showToast('✅ '+d.sent+' জনকে reminder পাঠানো হয়েছে!','#10b981');
        }
    }).catch(function(){
        if(btn){ btn.disabled=false; btn.textContent='🔔 এখনই Reminder পাঠান'; }
        res.textContent='❌ Network error';
    });
}

// ── FCM Push from Admin ─────────────────────────────────────
function onFcmTargetChange(){
    var t=document.getElementById('fcm_target').value;
    var bgWrap=document.getElementById('fcm_bg_wrap');
    if(bgWrap) bgWrap.style.display=(t==='blood_group')?'':'none';
}
function sendFcmPush(){
    var title=(document.getElementById('fcm_title').value||'').trim();
    var body =(document.getElementById('fcm_body').value||'').trim();
    var target=document.getElementById('fcm_target').value;
    var bg=document.getElementById('fcm_blood_group')?.value||'';
    var res=document.getElementById('fcm_push_result');
    if(!title||!body){alert('Title ও Message দিন।');return;}
    // Donor IDs from Donors tab if needed
    var donor_ids='';
    if(target==='donor_ids'){
        var cbs=document.querySelectorAll('.donors-cb:checked');
        if(!cbs.length){alert('Donors tab থেকে donor select করুন, তারপর Notifications এ আসুন।');return;}
        donor_ids=Array.from(cbs).map(cb=>cb.value).join(',');
    }
    if(!confirm('📲 '+target+' তে FCM push পাঠাবেন? Phone notification panel এ যাবে।'))return;
    const fd=new FormData();
    fd.append('act','send_fcm_push');
    fd.append('push_title',title);
    fd.append('push_body',body);
    fd.append('push_target',target);
    fd.append('blood_group',bg);
    fd.append('donor_ids',donor_ids);
    fd.append('csrf',CSRF);
    res.style.display='block';
    res.style.background='var(--inp)';res.style.color='var(--muted)';
    res.textContent='⏳ পাঠানো হচ্ছে...';
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        res.textContent=d.msg||'Done';
        if(d.ok){showToast('✅ FCM Push পাঠানো হয়েছে!','#10b981');}
    }).catch(()=>{res.textContent='❌ Network error';});
}

function sendBulkNotif(){
    const type=document.getElementById('bulk_notif_type').value;
    const msg=document.getElementById('bulk_notif_msg').value.trim();
    const res=document.getElementById('bulk_notif_result');
    if(!msg){alert('Message লিখুন।');return;}
    if(!confirm('📢 সব device এ notification পাঠাবেন?')) return;
    const fd=new FormData();
    fd.append('act','send_notif_bulk');
    fd.append('notif_type',type);
    fd.append('message',msg);
    fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.display='block';
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        res.textContent=d.msg||'Done';
        if(d.ok) document.getElementById('bulk_notif_msg').value='';
    }).catch(()=>{res.style.display='block';res.textContent='❌ Network error';});
}

function sendDonorNotif(){
    const phone=document.getElementById('donor_notif_phone').value.trim();
    const type=document.getElementById('donor_notif_type').value;
    const msg=document.getElementById('donor_notif_msg').value.trim();
    const res=document.getElementById('donor_notif_result');
    if(!phone||!msg){alert('Phone ও message দিন।');return;}
    const fd=new FormData();
    fd.append('act','send_notif_donor');
    fd.append('donor_phone',phone);
    fd.append('notif_type',type);
    fd.append('message',msg);
    fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.display='block';
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        res.textContent=d.msg||'Done';
        if(d.ok){ document.getElementById('donor_notif_msg').value=''; document.getElementById('donor_notif_phone').value=''; }
    }).catch(()=>{res.style.display='block';res.textContent='❌ Network error';});
}

// ── NOTIFY SELECTED DONORS ───────────────────────────────
function openNotifySelectedModal() {
    var cbs = document.querySelectorAll('.donors-cb:checked');
    if (!cbs.length) { alert('কোনো donor select করা হয়নি।'); return; }
    var modal = document.getElementById('notifySelModal');
    var countEl = document.getElementById('notifSelCount');
    if (countEl) countEl.textContent = '✅ ' + cbs.length + ' জন donor selected';
    var res = document.getElementById('sel_notif_result');
    if (res) res.style.display = 'none';
    var msgEl = document.getElementById('sel_notif_msg');
    if (msgEl) msgEl.value = '';
    modal.style.display = 'flex';
}

function closeNotifySelectedModal() {
    document.getElementById('notifySelModal').style.display = 'none';
}

function sendNotifySelected() {
    var cbs = document.querySelectorAll('.donors-cb:checked');
    if (!cbs.length) { alert('কোনো donor select করা হয়নি।'); return; }
    var ids = Array.from(cbs).map(cb => cb.value).join(',');
    var type = document.getElementById('sel_notif_type').value;
    var msg = (document.getElementById('sel_notif_msg').value||'').trim();
    var res = document.getElementById('sel_notif_result');
    if (!msg) { alert('Message লিখুন।'); return; }
    var fd = new FormData();
    fd.append('act', 'send_notif_selected_donors');
    fd.append('donor_ids', ids);
    fd.append('notif_type', type);
    fd.append('message', msg);
    fd.append('csrf', CSRF);
    fetch(window.location.href, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(function(d){
        res.style.display = 'block';
        res.style.background = d.ok ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)';
        res.style.color = d.ok ? 'var(--green)' : 'var(--red)';
        res.textContent = d.msg || 'Done';
        if (d.ok) {
            showToast(d.msg, '#10b981');
            setTimeout(closeNotifySelectedModal, 2000);
        }
    }).catch(function(){ res.style.display='block'; res.textContent='❌ Network error'; });
}

// ── TOKEN MANAGER ─────────────────────────────────────────
function addToken(){
    if(!guardSuper("Token Add")) return;
    const name=(document.getElementById('newTokenName').value||'').trim();
    if(!name){alert('Token এর নাম দিন।');return;}
    const fd=new FormData(); fd.append('act','add_token'); fd.append('token_name',name); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('newTokenName').value='';
            const list=document.getElementById('tokenList');
            // Remove "no tokens" placeholder if present
            const empty=list.querySelector('div[style*="text-align"]');
            if(empty) empty.remove();
            const row=document.createElement('div');
            row.className='token-row'; row.id='tokrow'+d.id;
            row.innerHTML=`
                <span class="tok-name">${escHtml(d.token_name)}</span>
                <span class="tok-val">${escHtml(d.token_value)}</span>
                <span class="tok-status tok-active" id="tok-status-${d.id}">✅ Active</span>
                <span class="tok-date">📅 ${escHtml(d.created_at)}</span>
                <button class="tok-copy" onclick="copyToken('${escHtml(d.token_value)}',this)">📋 Copy</button>
                <button class="tok-toggle active-btn" id="tok-toggle-${d.id}" onclick="toggleToken(${d.id})">⏸ Disable</button>
                <button class="tok-del" onclick="delToken(${d.id})">🗑</button>
            `;
            list.prepend(row);
            showToast('✅ Token তৈরি হয়েছে!');
        } else {alert('❌ '+(d.msg||'Token create failed'));}
    }).catch(e=>alert('Network error: '+e.message));
}

function copyToken(val, btn){
    navigator.clipboard.writeText(val).then(()=>{
        const old=btn.textContent; btn.textContent='✅ Copied!';
        setTimeout(()=>btn.textContent=old,1800);
    });
}

function toggleToken(id){
    const fd=new FormData(); fd.append('act','toggle_token'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const statusEl=document.getElementById('tok-status-'+id);
            const toggleEl=document.getElementById('tok-toggle-'+id);
            if(d.is_active){
                if(statusEl){statusEl.className='tok-status tok-active';statusEl.textContent='✅ Active';}
                if(toggleEl){toggleEl.className='tok-toggle active-btn';toggleEl.textContent='⏸ Disable';}
            } else {
                if(statusEl){statusEl.className='tok-status tok-inactive';statusEl.textContent='⛔ Inactive';}
                if(toggleEl){toggleEl.className='tok-toggle inactive-btn';toggleEl.textContent='▶ Enable';}
            }
        }
    }).catch(e=>alert('Network error: '+e.message));
}

function delToken(id){
    if(!guardSuper("Token Delete")) return;
    if(!confirm('🗑 এই Token delete করবেন?')) return;
    const fd=new FormData(); fd.append('act','del_token'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const row=document.getElementById('tokrow'+id);
            if(row){row.style.opacity='.2';setTimeout(()=>row.remove(),300);}
            showToast('✅ Token delete হয়েছে।');
        } else {alert('❌ '+(d.msg||'Delete failed'));}
    }).catch(e=>alert('Network error: '+e.message));
}

// ── IP WHITELIST ──────────────────────────────────────────
function toggleIpWhitelist(btn){
    if(btn.classList.contains('on')){
        if(!confirm('⚠️ IP Whitelist বন্ধ করলে যেকেউ panel access করতে পারবে। নিশ্চিত?')) return;
    } else {
        const ips=document.querySelectorAll('.ip-tag');
        if(ips.length===0){ alert('⚠️ আগে কমপক্ষে একটি IP add করুন। নইলে আপনি নিজেও panel access করতে পারবেন না!'); return; }
    }
    const fd=new FormData(); fd.append('act','toggle_ip_whitelist'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            btn.classList.toggle('on',d.enabled);
            showToast(d.enabled?'🔒 IP Whitelist চালু হয়েছে':'🔓 IP Whitelist বন্ধ হয়েছে', d.enabled?'#10b981':'#f59e0b');
        }
    }).catch(e=>alert('Network error: '+e.message));
}

function addMyIP(){
    const myIP='<?=esc($currentIP)?>';
    document.getElementById('newIpAddr').value=myIP;
    document.getElementById('newIpLabel').value='My IP';
    addIp();
}

function addIp(){
    if(!guardSuper("IP Add")) return;
    const ip=(document.getElementById('newIpAddr').value||'').trim();
    const label=(document.getElementById('newIpLabel').value||'').trim();
    if(!ip){alert('IP address দিন।');return;}
    const fd=new FormData(); fd.append('act','add_ip'); fd.append('ip_addr',ip); fd.append('ip_label',label); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('newIpAddr').value='';
            document.getElementById('newIpLabel').value='';
            const list=document.getElementById('ipList');
            const empty=list.querySelector('div[style*="text-align"]');
            if(empty) empty.remove();
            const tag=document.createElement('div');
            tag.className='ip-tag'; tag.id='iptag'+d.id;
            tag.innerHTML=`<span class="ip-addr">${escHtml(d.ip)}</span><span class="ip-label">${escHtml(d.label)}</span><span class="ip-badge active">✅ Active</span><button class="ip-del-btn" onclick="delIp(${d.id})">🗑 Remove</button>`;
            list.prepend(tag);
            showToast('✅ IP whitelist এ add হয়েছে।');
        } else {alert('❌ '+(d.msg||'Add IP failed'));}
    }).catch(e=>alert('Network error: '+e.message));
}

function delIp(id){
    if(!guardSuper("IP Remove")) return;
    if(!confirm('🗑 এই IP whitelist থেকে remove করবেন?')) return;
    const fd=new FormData(); fd.append('act','del_ip'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const tag=document.getElementById('iptag'+id);
            if(tag){tag.style.opacity='.2';setTimeout(()=>tag.remove(),300);}
            showToast('✅ IP remove হয়েছে।','#f59e0b');
        } else {alert('❌ '+(d.msg||'Remove failed'));}
    }).catch(e=>alert('Network error: '+e.message));
}

// ── CHANGE PASSWORD ───────────────────────────────────────
function changePassword(){
    if(!guardSuper("Password Change")) return;
    const cur=document.getElementById('pw_current').value;
    const nw=document.getElementById('pw_new').value;
    const cf=document.getElementById('pw_confirm').value;
    const btn=document.getElementById('pwSaveBtn');
    const res=document.getElementById('pwResult');
    if(!cur||!nw||!cf){res.className='pw-result err';res.style.display='block';res.textContent='সব field পূরণ করুন।';return;}
    btn.disabled=true; btn.textContent='⏳ সংরক্ষণ হচ্ছে...';
    const fd=new FormData(); fd.append('act','change_password');
    fd.append('current_pass',cur); fd.append('new_pass',nw); fd.append('confirm_pass',cf); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.textContent='🔑 Password পরিবর্তন করুন';
        res.style.display='block';
        if(d.ok){
            res.className='pw-result ok'; res.textContent=d.msg||'Password পরিবর্তন হয়েছে!';
            ['pw_current','pw_new','pw_confirm'].forEach(id=>document.getElementById(id).value='');
        } else { res.className='pw-result err'; res.textContent=d.msg||'Failed'; }
    }).catch(e=>{btn.disabled=false;btn.textContent='🔑 Password পরিবর্তন করুন';res.className='pw-result err';res.style.display='block';res.textContent='Network error: '+e.message;});
}

// ══ SITE CONFIG EDITOR ════════════════════════════════════
// Field schema → groups, labels, input type. Keys MUST match config.php defaults.
const CFG_GROUPS = [
  { title:'🏷️ Brand / Identity', fields:[
    ['BRAND_NAME','App Name','text'],['BRAND_SHORT','Short / PWA Name','text'],
    ['BRAND_TAGLINE','Tagline','text'],['ORG_NAME','Org Name (EN)','text'],
    ['ORG_NAME_BN','Org Name (BN)','text'],['APP_DESC','App Description','text'] ]},
  { title:'📞 Contact / Links', fields:[
    ['CONTACT_PHONE','Emergency Phone','text'],['SITE_URL','Site URL','text'],
    ['LOGO_PATH','Logo Path','text'],['ICON_PATH','Icon Path','text'] ]},
  { title:'🔗 Social Links', fields:[
    ['SOCIAL_FACEBOOK','Facebook','text'],['SOCIAL_TELEGRAM','Telegram','text'],
    ['SOCIAL_YOUTUBE','YouTube','text'],['SOCIAL_WHATSAPP','WhatsApp','text'] ]},
  { title:'🎨 Theme Colors', fields:[
    ['COLOR_PRIMARY','Primary','color'],['COLOR_PRIMARY_HOVER','Primary Hover','color'],
    ['COLOR_BG_MAIN','BG Main','color'],['COLOR_THEME','PWA Theme','color'] ]},
  { title:'⏳ Splash Screen', fields:[
    ['SPLASH_ENABLED','Enabled','bool'],['SPLASH_MIN_MS','Min ms','int'],
    ['SPLASH_MIN_MS_STANDALONE','Min ms (PWA)','int'],['SPLASH_MAX_MS','Max ms','int'],
    ['SPLASH_BG','BG (light)','color'],['SPLASH_BG_DARK','BG (dark)','color'] ]},
  { title:'🔥 Firebase', fb:true, fields:[
    ['apiKey','API Key','text'],['authDomain','Auth Domain','text'],['projectId','Project ID','text'],
    ['storageBucket','Storage Bucket','text'],['messagingSenderId','Messaging Sender ID','text'],
    ['appId','App ID','text'],['measurementId','Measurement ID','text'],['vapidKey','VAPID Key','text'] ]},
  { title:'🤖 Telegram / WhatsApp Bot', fields:[
    ['TELEGRAM_BOT_URL','Telegram Bot URL','text'],['TELEGRAM_BOT_SECRET','Telegram Secret','text'],
    ['TELEGRAM_BOT_USERNAME','Telegram Username','text'],['TELEGRAM_BOT_INSECURE_TLS','Telegram Insecure TLS','bool'],
    ['WA_BOT_URL','WhatsApp Bot URL','text'],['WA_BOT_SECRET','WhatsApp Secret','text'],
    ['WA_BOT_INSECURE_TLS','WhatsApp Insecure TLS','bool'],['VERIFY_OTP_TTL','OTP TTL (sec)','int'],
    ['PHONE_OTP_COUNTS_VERIFIED','Phone-OTP = Verified','bool'] ]},
  { title:'📎 Request Documents', fields:[
    ['AUTO_DELETE_DAYS','Auto-delete days','int'],['REQ_DOC_MAX_FILES','Max files/request','int'],
    ['REQ_DOC_MAX_MB','Max MB/file','int'],['REQ_DOC_TARGET_KB','Compress target KB','int'] ]},
];
let _cfgValues = {};

function cfgMsg(id,text,ok){ const e=document.getElementById(id); if(!e)return; e.style.display='block'; e.style.color=ok?'var(--green)':'var(--red)'; e.textContent=text; }
function cfgPost(extra){ const fd=new FormData(); fd.append('csrf',CSRF); for(const k in extra) fd.append(k,extra[k]);
  return fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()); }

function cfgUnlock(){
  const p=document.getElementById('cfgPass').value;
  if(!p){cfgMsg('cfgUnlockMsg','Password দিন।',false);return;}
  cfgPost({act:'cfg_unlock',cfg_pass:p}).then(d=>{
    if(d.ok){ document.getElementById('cfgPass').value=''; cfgMsg('cfgUnlockMsg','',true); cfgLoad(); }
    else cfgMsg('cfgUnlockMsg',d.msg||'ভুল password।',false);
  }).catch(e=>cfgMsg('cfgUnlockMsg','Network error: '+e.message,false));
}

function cfgLoad(){
  cfgPost({act:'cfg_get'}).then(d=>{
    if(!d.ok){ if(d.locked){ document.getElementById('cfgLock').style.display='block'; document.getElementById('cfgBody').style.display='none'; } cfgMsg('cfgUnlockMsg',d.msg||'Locked',false); return; }
    _cfgValues=d.values||{};
    document.getElementById('cfgLock').style.display='none';
    document.getElementById('cfgBody').style.display='block';
    cfgRender(d.values,d.defaults);
  });
}

// ── SMS Gateway ──────────────────────────────────────────
function smsLoad(){
  smsPost({act:'sms_get'}).then(d=>{
    if(!d.ok) return;
    const v=d.values||{};
    gid('sms_endpoint').value=v.SMS_ENDPOINT||'';
    gid('sms_server_address').value=v.SMS_SERVER_ADDRESS||'';
    gid('sms_username').value=v.SMS_USERNAME||'';
    gid('sms_password').value=v.SMS_PASSWORD||'';
    gid('sms_device_id').value=v.SMS_DEVICE_ID||'';
    gid('sms_auth_type').value=v.SMS_AUTH_TYPE||'basic';
    updateSmsToggle(v.SMS_ENABLED!=='false');
  });
}

function smsCollect(){
  return {
    SMS_ENDPOINT:       gid('sms_endpoint').value.trim(),
    SMS_SERVER_ADDRESS: gid('sms_server_address').value.trim(),
    SMS_USERNAME:       gid('sms_username').value.trim(),
    SMS_PASSWORD:       gid('sms_password').value,
    SMS_DEVICE_ID:      gid('sms_device_id').value.trim(),
    SMS_AUTH_TYPE:      gid('sms_auth_type').value,
    SMS_ENABLED:        _smsEnabled?'true':'false',
  };
}

function smsSave(){
  if(!guardSuper("SMS Gateway")) return;
  const btn=gid('smsSaveBtn'); btn.disabled=true; const old=btn.textContent; btn.textContent='⏳ সংরক্ষণ...';
  smsPost({act:'sms_save', sms:JSON.stringify(smsCollect())}).then(d=>{
    btn.disabled=false; btn.textContent=old;
    smsMsg(d.msg||(d.ok?'Saved':'Failed'),d.ok);
  }).catch(e=>{btn.disabled=false;btn.textContent=old;smsMsg('Network error: '+e.message,false);});
}

function smsTest(){
  gid('smsTestArea').style.display=gid('smsTestArea').style.display==='none'?'block':'none';
  gid('smsTestResult').textContent='';
}

function smsSendTest(){
  const phone=gid('smsTestPhone').value.trim()||'<?=defined('CONTACT_PHONE')?CONTACT_PHONE:''?>';
  if(!phone){ smsMsg('Test phone number দিন।',false); return; }
  const btn=gid('smsDoTestBtn'); if(btn) btn.disabled=true;
  smsPost({act:'sms_test', test_phone:phone}).then(d=>{
    if(btn) btn.disabled=false;
    gid('smsTestResult').textContent=d.msg||'';
    gid('smsTestResult').style.color=d.ok?'var(--green)':'var(--red)';
  }).catch(e=>{if(btn) btn.disabled=false;gid('smsTestResult').textContent='Network error';gid('smsTestResult').style.color='var(--red)';});
}

let _smsEnabled=true;
function smsToggleEnabled(el){
  _smsEnabled=!_smsEnabled;
  el.classList.toggle('on');
  updateSmsBadge();
}
function updateSmsToggle(on){
  _smsEnabled=on;
  const el=gid('smsEnabledToggle');
  if(el) el.classList.toggle('on',on);
  updateSmsBadge();
}
function updateSmsBadge(){
  const el=gid('smsEnabledBadge');
  if(el) el.textContent=_smsEnabled?'● Enabled':'● Disabled';
}
function smsTogglePass(){
  const el=gid('sms_password');
  el.type=el.type==='password'?'text':'password';
}
function smsPost(extra){
  const fd=new FormData(); fd.append('csrf',CSRF);
  for(const k in extra) fd.append(k,extra[k]);
  return fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json());
}
function smsMsg(msg,ok){
  const el=gid('smsMsg'); if(!el) return;
  el.textContent=msg||''; el.style.color=ok?'var(--green)':'var(--red)';
  if(msg) setTimeout(()=>{el.textContent='';},5000);
}
function gid(id){ return document.getElementById(id); }

function _cfgInput(key,type,val,fb){
  const id='cfgf_'+(fb?'FIREBASE__'+key:key);
  const esc=s=>String(s==null?'':s).replace(/"/g,'&quot;');
  if(type==='bool'){ const on=(val===true||val==='1'||val===1);
    return '<select id="'+id+'" style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);"><option value="1"'+(on?' selected':'')+'>On</option><option value="0"'+(!on?' selected':'')+'>Off</option></select>'; }
  if(type==='color'){
    return '<span style="display:flex;gap:8px;align-items:center;"><input type="color" id="'+id+'" value="'+esc(val)+'" style="width:42px;height:34px;padding:0;border:1px solid var(--bdr);border-radius:6px;background:none;cursor:pointer;"><input type="text" value="'+esc(val)+'" oninput="var c=document.getElementById(\''+id+'\');c.value=this.value;" style="flex:1;padding:8px 10px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);font-family:monospace;"></span>'; }
  const t=(type==='int')?'number':'text';
  return '<input type="'+t+'" id="'+id+'" value="'+esc(val)+'" style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid var(--bdr);background:var(--card2,#11141b);color:var(--fg,#e5e7eb);">';
}

function cfgRender(values,defaults){
  const wrap=document.getElementById('cfgForm'); let html='';
  CFG_GROUPS.forEach(g=>{
    html+='<div style="border:1px solid var(--bdr);border-radius:12px;padding:14px 16px;">';
    html+='<div style="font-weight:800;font-size:.92em;margin-bottom:12px;">'+g.title+'</div>';
    html+='<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">';
    g.fields.forEach(f=>{
      const [key,label,type]=f;
      let val;
      if(g.fb){ val=(values.FIREBASE&&values.FIREBASE[key]!=null)?values.FIREBASE[key]:''; }
      else    { val=(values[key]!=null)?values[key]:''; }
      html+='<div><label style="display:block;font-size:.74em;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-bottom:5px;">'+label+'</label>'+_cfgInput(key,type,val,g.fb)+'</div>';
    });
    html+='</div></div>';
  });
  wrap.innerHTML=html;
}

function cfgCollect(){
  const out={}; const fb={};
  CFG_GROUPS.forEach(g=>{
    g.fields.forEach(f=>{
      const [key,,type]=f;
      const id='cfgf_'+(g.fb?'FIREBASE__'+key:key);
      const el=document.getElementById(id); if(!el) return;
      let v=el.value;
      if(g.fb){ fb[key]=v; } else { out[key]=v; }
    });
  });
  out.FIREBASE=fb;
  return out;
}

function cfgSave(){
  if(!guardSuper("Site Config")) return;
  const btn=document.getElementById('cfgSaveBtn'); btn.disabled=true; const old=btn.textContent; btn.textContent='⏳ সংরক্ষণ...';
  cfgPost({act:'cfg_save', cfg:JSON.stringify(cfgCollect())}).then(d=>{
    btn.disabled=false; btn.textContent=old;
    cfgMsg('cfgSaveMsg',d.msg||(d.ok?'Saved':'Failed'),d.ok);
  }).catch(e=>{btn.disabled=false;btn.textContent=old;cfgMsg('cfgSaveMsg','Network error: '+e.message,false);});
}

function cfgReset(){
  if(!guardSuper("Site Config")) return;
  if(!confirm('♻️ সব setting আবার default-এ ফিরিয়ে নেবেন? (config_overrides.json মুছে যাবে)')) return;
  cfgPost({act:'cfg_reset'}).then(d=>{ cfgMsg('cfgSaveMsg',d.msg||(d.ok?'Reset':'Failed'),d.ok); if(d.ok) cfgLoad(); });
}

function cfgLockNow(){
  document.getElementById('cfgBody').style.display='none';
  document.getElementById('cfgLock').style.display='block';
  // server-side session flag persists until logout; this is a client-side hide only.
}

function cfgChangePass(){
  if(!guardSuper("Site Config")) return;
  const cur=document.getElementById('cfgCurPass').value, nw=document.getElementById('cfgNewPass').value, cf=document.getElementById('cfgCnfPass').value;
  if(!cur||!nw||!cf){cfgMsg('cfgPassMsg','সব field পূরণ করুন।',false);return;}
  cfgPost({act:'cfg_change_pass',cur_pass:cur,new_pass:nw,confirm_pass:cf}).then(d=>{
    cfgMsg('cfgPassMsg',d.msg||(d.ok?'Changed':'Failed'),d.ok);
    if(d.ok){['cfgCurPass','cfgNewPass','cfgCnfPass'].forEach(id=>document.getElementById(id).value='');}
  }).catch(e=>cfgMsg('cfgPassMsg','Network error: '+e.message,false));
}

// ── ADVANCED SEARCH ──────────────────────────────────────
function runAdvSearch(){
    const btn=document.querySelector('.adv-run'); const resEl=document.getElementById('advResults'); const countEl=document.getElementById('advCount');
    btn.disabled=true; btn.innerHTML='<span class="loading-spin"></span> Searching...';
    resEl.innerHTML='<div style="text-align:center;padding:30px;color:var(--muted);">⏳ খোঁজা হচ্ছে...</div>';
    countEl.textContent='';
    const fd=new FormData(); fd.append('act','adv_search'); fd.append('csrf',CSRF);
    fd.append('s_name',document.getElementById('as_name').value.trim());
    fd.append('s_phone',document.getElementById('as_phone').value.trim());
    fd.append('s_group',document.getElementById('as_group').value);
    fd.append('s_loc',document.getElementById('as_loc').value.trim());
    fd.append('s_status',document.getElementById('as_status').value);
    fd.append('s_badge',document.getElementById('as_badge').value);
    fd.append('s_from',document.getElementById('as_from').value);
    fd.append('s_to',document.getElementById('as_to').value);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.innerHTML='🔍 Search';
        if(!d.ok){resEl.innerHTML='<div class="empty">❌ Error: '+(d.msg||'Unknown')+'</div>';return;}
        _advRows=d.rows; countEl.textContent=d.count+' টি পাওয়া গেছে';
        document.getElementById('advExportBtn').style.display=d.count?'inline-block':'none';
        if(!d.rows.length){resEl.innerHTML='<div class="empty">🔍 কোনো donor পাওয়া যায়নি।</div>';return;}
        const stCls={Available:'st-av','Not Available':'st-na','Not Willing':'st-nw'};
        const stIco={Available:'✔','Not Available':'✖','Not Willing':'⛔'};
        const badgeIcons={New:'🌱',Active:'⭐',Hero:'🦸',Legend:'👑'};
        let html='<div class="ow"><table><thead><tr><th>#</th><th>Name</th><th>Group</th><th>Phone</th><th>Location</th><th>Status</th><th>Badge</th><th>Donations</th><th>Last Donation</th><th>Joined</th></tr></thead><tbody>';
        d.rows.forEach((r,i)=>{
            const st=r._status||'Available';
            const jn=r.created_at?r.created_at.substring(0,10):'—';
            const ld=r.last_donation==='no'||!r.last_donation?'Never':r.last_donation;
            const bl=r.badge_level||'New';
            html+=`<tr><td style="color:var(--muted);">${i+1}</td><td style="font-weight:600;">${escHtml(r.name||'')}</td><td><span class="bg">${escHtml(r.blood_group||'')}</span></td><td style="font-family:monospace;font-size:.79em;">${escHtml(r.phone||'')}</td><td style="font-size:.79em;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(r.location||'')}</td><td class="${stCls[st]||''}" style="white-space:nowrap;">${stIco[st]||''} ${escHtml(st)}</td><td><span class="badge-pill bp-${bl.toLowerCase()}">${badgeIcons[bl]||'🌱'} ${escHtml(bl)}</span></td><td style="text-align:center;">${parseInt(r.total_donations)||0}</td><td style="font-size:.78em;color:var(--muted);">${escHtml(ld)}</td><td style="font-size:.77em;color:var(--muted);">${escHtml(jn)}</td></tr>`;
        });
        html+='</tbody></table></div>'; resEl.innerHTML=html;
    }).catch(()=>{btn.disabled=false;btn.innerHTML='🔍 Search';resEl.innerHTML='<div class="empty">❌ Network error. আবার চেষ্টা করুন।</div>';});
}

function clearAdvSearch(){
    ['as_name','as_phone','as_loc','as_from','as_to'].forEach(id=>document.getElementById(id).value='');
    ['as_group','as_status','as_badge'].forEach(id=>document.getElementById(id).value='All');
    document.getElementById('advResults').innerHTML='<div style="text-align:center;padding:40px;color:var(--muted);"><div style="font-size:2.5rem;margin-bottom:10px;">🔍</div><p>উপরের filter দিয়ে donor খুঁজুন</p></div>';
    document.getElementById('advCount').textContent='';
    document.getElementById('advExportBtn').style.display='none';
    _advRows=[];
}

function exportAdvCSV(){
    if(!_advRows.length){alert('কোনো data নেই।');return;}
    let csv='#,Name,Phone,Blood Group,Location,Status,Badge,Donations,Last Donation,Joined\n';
    _advRows.forEach((r,i)=>{
        const ld=r.last_donation==='no'||!r.last_donation?'Never':r.last_donation;
        const jn=r.created_at?r.created_at.substring(0,10):'';
        csv+=`${i+1},"${(r.name||'').replace(/"/g,'""')}","${r.phone||''}","${r.blood_group||''}","${(r.location||'').replace(/"/g,'""')}","${r._status||''}","${r.badge_level||''}",${parseInt(r.total_donations)||0},"${ld}","${jn}"\n`;
    });
    const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
    const a=document.createElement('a'); a.href=URL.createObjectURL(blob);
    a.download='bloodarena_search_'+new Date().toISOString().substring(0,10)+'.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

// ── EDIT DONOR MODAL ─────────────────────────────────────
let _editMapPickerMap=null, _editMapPickerMarker=null;

function openEditDonor(id,btn){
    btn.disabled=true; btn.textContent='⏳';
    const fd=new FormData(); fd.append('act','get_donor'); fd.append('id',id); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.textContent='✏️';
        if(!d.ok){alert(d.msg||'Error loading donor');return;}
        const donor=d.donor;
        document.getElementById('edit_donor_id').value=donor.id;
        document.getElementById('edit_name').value=donor.name||'';
        document.getElementById('edit_phone').value=donor.phone||'';
        document.getElementById('edit_total_donations').value=donor.total_donations||0;
        updateEditBadgePreview(donor.total_donations||0);
        document.getElementById('edit_location').value=donor.location||'';
        document.getElementById('edit_reg_geo').value='';
        const bgSel=document.getElementById('edit_blood_group');
        for(let i=0;i<bgSel.options.length;i++){if(bgSel.options[i].value===donor.blood_group){bgSel.selectedIndex=i;break;}}
        if(!donor.last_donation_fmt||donor.last_donation_fmt==='no'){clearEditDate();}
        else {
            const p=donor.last_donation_fmt.split('/');
            if(p.length===3){
                const iso=p[2]+'-'+p[1]+'-'+p[0];
                document.getElementById('edit_last_date').value=iso;
                document.getElementById('edit_last_donation').value=donor.last_donation_fmt;
                document.getElementById('editDateDisplay').textContent=donor.last_donation_fmt;
            }
        }
        setEditWilling(donor.willing_to_donate==='no'?'no':'yes');
        const geoSt=document.getElementById('editGeoStatus');
        if(donor.geo_lat&&donor.geo_lng){geoSt.style.display='block';geoSt.textContent='📍 Existing: '+donor.geo_lat+', '+donor.geo_lng;}
        else{geoSt.style.display='none';}
        document.getElementById('editDonorModal').style.display='flex';
    }).catch(e=>{btn.disabled=false;btn.textContent='✏️';alert('Network error: '+e.message);});
}

function closeEditModal(){ document.getElementById('editDonorModal').style.display='none'; }

function updateEditBadgePreview(n){
    n=parseInt(n)||0;
    var icon,label;
    if(n>=10){icon='👑';label='Legend';}else if(n>=5){icon='🦸';label='Hero';}else if(n>=2){icon='⭐';label='Active';}else{icon='🌱';label='New';}
    var el=document.getElementById('editBadgePreview'); if(el) el.textContent=icon+' '+label+' Donor হবেন';
}

function setEditWilling(val){
    document.getElementById('edit_willing').value=val;
    document.getElementById('ef_yes_btn').className=val==='yes'?'on-yes':'';
    document.getElementById('ef_no_btn').className=val==='no'?'on-no':'';
}

function syncEditDate(val){
    if(!val){clearEditDate();return;}
    const p=val.split('-');
    if(p.length===3){
        const ddmmyyyy=p[2]+'/'+p[1]+'/'+p[0];
        document.getElementById('edit_last_donation').value=ddmmyyyy;
        document.getElementById('editDateDisplay').textContent=ddmmyyyy;
    }
}

function clearEditDate(){
    document.getElementById('edit_last_date').value='';
    document.getElementById('edit_last_donation').value='no';
    document.getElementById('editDateDisplay').textContent='Never donated';
}

function saveEditDonor(){
    const id=document.getElementById('edit_donor_id').value;
    const name=document.getElementById('edit_name').value.trim();
    const phone=document.getElementById('edit_phone').value.trim();
    const bg=document.getElementById('edit_blood_group').value;
    const loc=document.getElementById('edit_location').value.trim();
    const last=document.getElementById('edit_last_donation').value;
    const willing=document.getElementById('edit_willing').value;
    const total=parseInt(document.getElementById('edit_total_donations').value)||0;
    const regGeo=document.getElementById('edit_reg_geo').value.trim();
    if(!name) return alert('নাম দিন।');
    if(!phone) return alert('Phone দিন।');
    if(!loc) return alert('Location দিন।');
    const btn=document.getElementById('editSaveBtn'); btn.disabled=true; btn.textContent='⏳ Saving...';
    const fd=new FormData(); fd.append('act','edit_donor'); fd.append('id',id);
    fd.append('name',name); fd.append('phone',phone); fd.append('blood_group',bg); fd.append('location',loc);
    fd.append('last_donation',last); fd.append('willing_to_donate',willing); fd.append('total_donations',total);
    if(regGeo) fd.append('reg_geo',regGeo); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.textContent='💾 Save Changes';
        if(d.ok){
            closeEditModal();
            const row=document.getElementById('drow'+id);
            if(row){
                const badgeMap=total>=10?'Legend':total>=5?'Hero':total>=2?'Active':'New';
                const badgeIcon=total>=10?'👑':total>=5?'🦸':total>=2?'⭐':'🌱';
                const tds=row.querySelectorAll('td');
                if(tds[2]) tds[2].innerHTML='<span style="font-weight:600">'+escHtml(name)+' '+badgeIcon+'</span>';
                if(tds[3]) tds[3].innerHTML='<span class="bg">'+escHtml(bg)+'</span>';
                if(tds[4]) tds[4].textContent=phone;
                if(tds[7]) tds[7].innerHTML=badgeIcon+' '+badgeMap;
                if(tds[8]) tds[8].textContent=total;
                row.style.background='rgba(59,130,246,.08)'; setTimeout(()=>row.style.background='',2000);
            }
            showToast('✅ '+name+' updated!');
        } else {alert('❌ '+(d.msg||'Update failed'));}
    }).catch(e=>{btn.disabled=false;btn.textContent='💾 Save Changes';alert('Network error: '+e.message);});
}

// ── Map Picker ───────────────────────────────────────────
function openEditMapPicker(){
    if(document.getElementById('adminMapOverlay')){
        document.getElementById('adminMapOverlay').style.display='flex';
        setTimeout(()=>{if(_editMapPickerMap)_editMapPickerMap.invalidateSize();},300);return;
    }
    const overlay=document.createElement('div'); overlay.id='adminMapOverlay';
    overlay.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:5000;display:flex;flex-direction:column;';
    overlay.innerHTML=`<div style="background:#141720;padding:10px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid rgba(255,255,255,.1);">
        <input id="adminMapSearch" type="text" placeholder="🔍 এলাকার নাম লিখুন..." style="flex:1;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:7px 11px;color:#fff;font-size:.88em;outline:none;">
        <button onclick="doAdminMapSearch()" style="padding:7px 14px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;">🔍</button>
        <button onclick="adminMapMyLoc()" style="padding:7px 11px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#10b981;border-radius:8px;cursor:pointer;">📍</button>
        <button onclick="confirmAdminMapLocation()" style="padding:7px 14px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:.84em;font-weight:700;cursor:pointer;">✅ ব্যবহার করুন</button>
        <button onclick="closeAdminMapPicker()" style="padding:7px 11px;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#ef4444;border-radius:8px;cursor:pointer;font-size:.88em;">✕</button>
    </div>
    <div id="adminMapResult" style="background:#0d1117;padding:5px 16px;font-size:.78em;color:#6ee7b7;min-height:24px;"></div>
    <div id="adminLeafletMap" style="flex:1;"></div>`;
    document.body.appendChild(overlay);
    if(!document.getElementById('leaflet-css')){
        const lc=document.createElement('link'); lc.id='leaflet-css'; lc.rel='stylesheet';
        lc.href='https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'; document.head.appendChild(lc);
    }
    function initAdminMap(){
        if(typeof L==='undefined'){setTimeout(initAdminMap,400);return;}
        _editMapPickerMap=L.map('adminLeafletMap',{zoomControl:true}).setView([23.7735,90.3742],13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',subdomains:'abcd',maxZoom:19}).addTo(_editMapPickerMap);
        _editMapPickerMap.on('click',function(e){
            const lat=e.latlng.lat.toFixed(6),lng=e.latlng.lng.toFixed(6);
            if(_editMapPickerMarker)_editMapPickerMarker.setLatLng(e.latlng);
            else{_editMapPickerMarker=L.marker(e.latlng,{draggable:true}).addTo(_editMapPickerMap);_editMapPickerMarker.on('dragend',function(){const p=_editMapPickerMarker.getLatLng();doAdminReverseGeocode(p.lat.toFixed(6),p.lng.toFixed(6));});}
            doAdminReverseGeocode(lat,lng);
        });
        if(navigator.geolocation){navigator.geolocation.getCurrentPosition(p=>{_editMapPickerMap.setView([p.coords.latitude,p.coords.longitude],15);},null,{timeout:5000});}
    }
    if(!document.getElementById('leaflet-js')){
        const ls=document.createElement('script'); ls.id='leaflet-js';
        ls.src='https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'; ls.onload=initAdminMap; document.head.appendChild(ls);
    } else {setTimeout(initAdminMap,200);}
}

function doAdminReverseGeocode(lat,lng){
    const res=document.getElementById('adminMapResult'); if(res) res.textContent='⏳ লোড হচ্ছে...';
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`,{headers:{'Accept-Language':'en'}})
    .then(r=>r.json()).then(d=>{
        const addr=d.address||{};
        const parts=[addr.road||addr.neighbourhood||addr.suburb,addr.city_district||addr.suburb||addr.town||addr.city,addr.city||addr.county].filter(Boolean);
        const readable=parts.length?parts.join(', '):d.display_name;
        if(res) res.textContent='📍 '+readable;
        if(_editMapPickerMarker) _editMapPickerMarker.bindPopup('📍 '+readable).openPopup();
        const ol=document.getElementById('adminMapOverlay'); if(ol){ol.dataset.lat=lat;ol.dataset.lng=lng;ol.dataset.label=readable;}
    }).catch(()=>{
        if(res) res.textContent='Lat: '+lat+', Lon: '+lng;
        const ol=document.getElementById('adminMapOverlay'); if(ol){ol.dataset.lat=lat;ol.dataset.lng=lng;ol.dataset.label='Lat:'+lat+',Lon:'+lng;}
    });
}

function doAdminMapSearch(){
    const q=(document.getElementById('adminMapSearch')||{}).value; if(!q||!q.trim()) return;
    fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q.trim()+', Bangladesh')+'&limit=1&accept-language=en')
    .then(r=>r.json()).then(results=>{
        if(!results||!results.length){alert('এলাকা খুঁজে পাওয়া যায়নি।');return;}
        const r=results[0]; const lat=parseFloat(r.lat),lng=parseFloat(r.lon);
        _editMapPickerMap.setView([lat,lng],16);
        const latlng=L.latLng(lat,lng);
        if(_editMapPickerMarker)_editMapPickerMarker.setLatLng(latlng);
        else{_editMapPickerMarker=L.marker(latlng,{draggable:true}).addTo(_editMapPickerMap);_editMapPickerMarker.on('dragend',function(){const p=_editMapPickerMarker.getLatLng();doAdminReverseGeocode(p.lat.toFixed(6),p.lng.toFixed(6));});}
        doAdminReverseGeocode(lat.toFixed(6),lng.toFixed(6));
    }).catch(()=>alert('Search কাজ করছে না।'));
}

function adminMapMyLoc(){
    if(!navigator.geolocation||!_editMapPickerMap) return;
    navigator.geolocation.getCurrentPosition(p=>{
        const lat=p.coords.latitude,lng=p.coords.longitude; _editMapPickerMap.setView([lat,lng],16);
        const latlng=L.latLng(lat,lng);
        if(_editMapPickerMarker)_editMapPickerMarker.setLatLng(latlng);
        else{_editMapPickerMarker=L.marker(latlng,{draggable:true}).addTo(_editMapPickerMap);_editMapPickerMarker.on('dragend',function(){const p2=_editMapPickerMarker.getLatLng();doAdminReverseGeocode(p2.lat.toFixed(6),p2.lng.toFixed(6));});}
        doAdminReverseGeocode(lat.toFixed(6),lng.toFixed(6));
    },null,{timeout:8000,enableHighAccuracy:true});
}

function confirmAdminMapLocation(){
    const ol=document.getElementById('adminMapOverlay'); if(!ol||!ol.dataset.lat){alert('Map এ ক্লিক করে location বেছে নিন।');return;}
    const label=ol.dataset.label||('Lat:'+ol.dataset.lat+',Lon:'+ol.dataset.lng);
    document.getElementById('edit_location').value=label;
    document.getElementById('edit_reg_geo').value='Lat: '+ol.dataset.lat+', Lon: '+ol.dataset.lng;
    const geoSt=document.getElementById('editGeoStatus'); geoSt.style.display='block';
    geoSt.textContent='📍 '+ol.dataset.lat+', '+ol.dataset.lng; closeAdminMapPicker();
}

function closeAdminMapPicker(){
    const ol=document.getElementById('adminMapOverlay'); if(ol) ol.style.display='none';
    if(_editMapPickerMap) setTimeout(()=>_editMapPickerMap.invalidateSize(),300);
}

// ── Utility ──────────────────────────────────────────────
function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Session timer & idle ─────────────────────────────────
let sessionStart=Date.now();
document.addEventListener('mousemove',()=>lastActivity=Date.now());
document.addEventListener('keydown',  ()=>lastActivity=Date.now());
document.addEventListener('touchstart',()=>lastActivity=Date.now());
function formatTime(s){const m=Math.floor(s/60),ss=s%60;return m+':'+(ss<10?'0':'')+ss;}
setInterval(()=>{
    const el=document.getElementById('sClock'); if(el) el.textContent=formatTime(Math.floor((Date.now()-sessionStart)/1000));
    const idleS=Math.floor((Date.now()-lastActivity)/1000); const rem=IDLE_LIMIT-idleS;
    const warn=document.getElementById('idleWarn'); const sEl=document.getElementById('idleSecs');
    if(rem<=60&&rem>0){if(warn){warn.style.display='block';if(sEl)sEl.textContent=rem;}}
    else if(rem<=0){window.location.href='admin.php?logout=1';}
    else{if(warn)warn.style.display='none';}
},1000);

// ── Init ─────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('#tab-advsearch input, #tab-advsearch select').forEach(el=>{
        el.addEventListener('keydown',function(e){if(e.key==='Enter') runAdvSearch();});
    });
    // Init admin notifications
    adminNotifInit();
    // Load SMS gateway config
    if(document.getElementById('sms_endpoint')) smsLoad();
});

document.addEventListener('contextmenu',e=>e.preventDefault());
document.addEventListener('keydown',e=>{if(e.ctrlKey&&(e.key==='u'||e.key==='U'||e.key==='s'||e.key==='S'))e.preventDefault();});

// ============================================================
// ADMIN BROWSER NOTIFICATIONS — SW polling system
// ============================================================
var _adminSWReg      = null;   // SW registration
var _adminPollTimer  = null;   // setInterval handle
var _adminLastTs     = 0;      // last seen unix timestamp
var _adminPollOn     = localStorage.getItem('adm_poll_on') !== '0'; // default on
var _adminNotifEnabled = false;

function adminNotifInit() {
    _adminLastTs = Math.floor(Date.now()/1000) - 5; // start from now minus buffer
    _updatePollToggleUI();
    _checkNotifStatus();
    // Register SW (same sw.js as index.php)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', {scope:'/'})
        .then(function(reg) {
            _adminSWReg = reg;
            _checkNotifStatus();
        }).catch(function(err){
            console.warn('[Admin SW]', err);
        });
    }
    // Start polling if enabled
    if (_adminPollOn) _startAdminPoll();
}

function _checkNotifStatus() {
    var statusEl = document.getElementById('adminNotifStatus');
    var enableBtn = document.getElementById('adminNotifEnableBtn');
    var testBtn  = document.getElementById('adminNotifTestBtn');
    if (!statusEl) return;

    if (!('Notification' in window)) {
        statusEl.textContent = '❌ এই browser এ Notification support নেই।';
        statusEl.style.background = 'rgba(239,68,68,.1)';
        statusEl.style.borderColor = 'rgba(239,68,68,.3)';
        if (enableBtn) enableBtn.style.display = 'none';
        return;
    }
    var perm = Notification.permission;
    if (perm === 'granted') {
        statusEl.innerHTML = '✅ <strong style="color:var(--green);">Notifications চালু আছে</strong> — নতুন Inbox message এলে notification আসবে।';
        statusEl.style.background = 'rgba(16,185,129,.08)';
        statusEl.style.borderColor = 'rgba(16,185,129,.3)';
        if (enableBtn) enableBtn.textContent = '🔕 Disable করুন';
        if (testBtn) testBtn.style.display = '';
        _adminNotifEnabled = true;
        var dot = document.getElementById('adminNotifDot');
        if (dot) dot.style.display = '';
    } else if (perm === 'denied') {
        statusEl.innerHTML = '🚫 <strong style="color:var(--red);">Notifications Blocked</strong> — Browser settings থেকে manually allow করুন।';
        statusEl.style.background = 'rgba(239,68,68,.1)';
        statusEl.style.borderColor = 'rgba(239,68,68,.3)';
        if (enableBtn) enableBtn.textContent = '🔒 Browser-এ Blocked';
        _adminNotifEnabled = false;
    } else {
        statusEl.innerHTML = '⚠️ Notifications এখনো allow করা হয়নি।';
        statusEl.style.background = 'rgba(245,158,11,.08)';
        statusEl.style.borderColor = 'rgba(245,158,11,.3)';
        if (enableBtn) enableBtn.textContent = '🔔 Enable Notifications';
        _adminNotifEnabled = false;
    }
}

function adminEnableNotif() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') {
        // Toggle off — just update UI note (can't programmatically remove)
        showToast('Browser settings থেকে manually block করুন।', '#f59e0b');
        return;
    }
    Notification.requestPermission().then(function(perm){
        _checkNotifStatus();
        if (perm === 'granted') {
            showToast('✅ Notifications চালু হয়েছে!');
            if (!_adminPollOn) toggleAdminPoll(document.getElementById('adminPollToggle'));
        }
    });
}

function adminTestNotif() {
    _showAdminNotif('🧪 Test Notification', 'Admin notifications ঠিকমতো কাজ করছে!', '/admin.php');
    showToast('✅ Test notification পাঠানো হয়েছে।');
}

function _showAdminNotif(title, body, url) {
    if (Notification.permission !== 'granted') return;
    var opts = {
        body: body,
        icon: '/icon.png',
        badge: '/?badge_icon=1',
        tag: 'admin-' + Date.now(),
        renotify: true,
        vibrate: [200, 100, 200],
        data: { url: url || '/admin.php' }
    };
    if (_adminSWReg) {
        _adminSWReg.showNotification(title, opts).catch(function(){
            new Notification(title, opts);
        });
    } else {
        new Notification(title, opts);
    }
}

// ── Clear Service Notifications (from Notifications tab) ────
function clearAllServiceNotifs(){
    if(!confirm('🗑 DB-তে জমা থাকা সব service notification মুছে ফেলবেন?')) return;
    const res=document.getElementById('svcNotifClearResult');
    res.style.display='block'; res.style.background='var(--inp)'; res.style.color='var(--muted)'; res.textContent='⏳ মুছছে...';
    const fd=new FormData(); fd.append('act','clear_service_notifs'); fd.append('mode','all'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        res.textContent=d.msg||'Done';
        if(d.ok) showToast(d.msg,'#10b981');
    }).catch(()=>{res.textContent='❌ Network error';});
}
function clearReadServiceNotifs(){
    if(!confirm('✓ শুধু read হওয়া service notification মুছে ফেলবেন?')) return;
    const res=document.getElementById('svcNotifClearResult');
    res.style.display='block'; res.style.background='var(--inp)'; res.style.color='var(--muted)'; res.textContent='⏳ মুছছে...';
    const fd=new FormData(); fd.append('act','clear_service_notifs'); fd.append('mode','read'); fd.append('csrf',CSRF);
    fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{
        res.style.background=d.ok?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)';
        res.style.color=d.ok?'var(--green)':'var(--red)';
        res.textContent=d.msg||'Done';
        if(d.ok) showToast(d.msg,'#10b981');
    }).catch(()=>{res.textContent='❌ Network error';});
}

// ── Polling ───────────────────────────────────────────────
function _startAdminPoll() {
    if (_adminPollTimer) return;
    _pollAdminCounts(); // immediate first poll
    _adminPollTimer = setInterval(function(){
        if (!document.hidden) _pollAdminCounts();
    }, 30000);
    document.addEventListener('visibilitychange', function(){
        if (!document.hidden) _pollAdminCounts();
    });
}

function _stopAdminPoll() {
    if (_adminPollTimer) { clearInterval(_adminPollTimer); _adminPollTimer = null; }
}

function toggleAdminPoll(btn) {
    _adminPollOn = !_adminPollOn;
    localStorage.setItem('adm_poll_on', _adminPollOn ? '1' : '0');
    _updatePollToggleUI();
    if (_adminPollOn) { _startAdminPoll(); showToast('✅ Auto-polling চালু হয়েছে।'); }
    else { _stopAdminPoll(); showToast('⏸ Auto-polling বন্ধ।', '#f59e0b'); }
}

function _updatePollToggleUI() {
    var btn = document.getElementById('adminPollToggle');
    var lbl = document.getElementById('adminPollStatus');
    if (btn) btn.className = 'toggle-btn' + (_adminPollOn ? ' on' : '');
    if (lbl) lbl.textContent = _adminPollOn ? '✅ প্রতি ৩০s এ check করছে' : '⏸ বন্ধ';
}

var _lastKnownInboxCount = -1;
function _pollAdminCounts() {
    var fd = new FormData();
    fd.append('act', 'get_admin_poll');
    fd.append('since', _adminLastTs);
    fd.append('csrf', CSRF);
    fetch(window.location.href, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(function(d){
        if (!d.ok) return;

        // Update _adminLastTs to latest server time
        if (d.server_time) _adminLastTs = d.server_time;

        // Update sidebar badges
        _updateSidebarBadge('inbox', d.total_inbox);

        // ── Auto-refresh inbox if tab is open and count changed ──
        var inboxTab = document.getElementById('tab-inbox');
        var inboxVisible = inboxTab && inboxTab.classList.contains('on');
        if (d.total_inbox !== undefined && d.total_inbox !== _lastKnownInboxCount) {
            if (inboxVisible && typeof loadInbox === 'function') loadInbox(_inboxFilter || 'all');
            _lastKnownInboxCount = d.total_inbox;
        }

        // ── Trigger auto-reminder if due ──
        if (d.auto_reminder_due) {
            var rfd = new FormData();
            rfd.append('act', 'run_auto_reminder');
            rfd.append('csrf', CSRF);
            fetch(window.location.href, {method:'POST', body:rfd, headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.json()).then(function(rd){
                if(rd.ok && !rd.skipped && rd.sent > 0){
                    showToast('🔔 Auto Reminder: ' + rd.sent + ' জন not-willing donor কে notification পাঠানো হয়েছে।', '#f59e0b');
                }
            }).catch(function(){});
        }

        // Fire browser notifications for NEW items
        if (_adminNotifEnabled) {
            if (d.new_inbox && d.new_inbox.length) {
                d.new_inbox.forEach(function(m){
                    _showAdminNotif(
                        '📬 নতুন Message: ' + (m.sender_name||''),
                        m.message ? m.message.substring(0,80) + (m.message.length>80?'…':'') : '',
                        '/admin.php'
                    );
                });
            }
        }
    }).catch(function(){});
}

function _updateSidebarBadge(tabName, count) {
    // Find .ni and .mt elements for this tab
    var niEls = document.querySelectorAll('.ni[onclick*="\''+tabName+'\'"], .mt[onclick*="\''+tabName+'\'"]');
    niEls.forEach(function(el){
        var cnt = el.querySelector('.cnt');
        if (count > 0) {
            if (cnt) { cnt.textContent = count; }
            else {
                var sp = document.createElement('span');
                sp.className = 'cnt'; sp.textContent = count;
                el.appendChild(sp);
            }
        } else {
            if (cnt) cnt.remove();
        }
    });
};
</script>
<?php endif; ?>
<!-- ══ NOTIFY SELECTED DONORS MODAL ══ -->
<div id="notifySelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--card);border:1px solid var(--bdr);border-radius:16px;padding:24px;max-width:460px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.5);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="margin:0;font-size:1rem;">📢 Selected Donors কে Notify করুন</h3>
      <button onclick="closeNotifySelectedModal()" style="background:none;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer;padding:0 4px;">✕</button>
    </div>
    <p id="notifSelCount" style="font-size:.82em;color:var(--blue);margin-bottom:12px;"></p>
    <div style="display:grid;gap:10px;">
      <div>
        <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Notification Type</label>
        <select id="sel_notif_type" style="padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;width:100%;">
          <option value="info">ℹ️ Info (General)</option>
          <option value="warning">⚠️ Warning</option>
          <option value="location_on">📍 Location চালু করুন</option>
          <option value="notif_on">🔔 Notification চালু করুন</option>
        </select>
      </div>
      <div>
        <label style="font-size:.78em;color:var(--muted);display:block;margin-bottom:4px;">Message</label>
        <textarea id="sel_notif_msg" rows="3" placeholder="Notification message লিখুন..." style="width:100%;padding:9px;background:var(--inp);border:1px solid var(--bdr);border-radius:8px;color:var(--text);font-size:.85em;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div id="sel_notif_result" style="display:none;font-size:.83em;padding:8px 12px;border-radius:8px;"></div>
      <div style="display:flex;gap:8px;">
        <button onclick="sendNotifySelected()" style="flex:1;padding:10px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.88em;">📤 পাঠান</button>
        <button onclick="closeNotifySelectedModal()" style="padding:10px 18px;background:var(--inp);color:var(--muted);border:1px solid var(--bdr);border-radius:8px;cursor:pointer;font-size:.88em;">বাতিল</button>
      </div>
    </div>
  </div>
</div>
<!-- 🌐 i18n: dictionary + standalone engine (admin language toggle) -->
<script><?php include __DIR__ . '/assets/i18n-dict.js.php'; ?></script>
<script><?php include __DIR__ . '/assets/i18n-engine.js.php'; ?></script>
</body>
</html>
<?php ob_end_flush(); ?>
