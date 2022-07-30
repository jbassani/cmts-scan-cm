<?php 
/* 
 *  FUNÇÕES
 */

function getMac($ip)
{
    $mac='';
    if ($ip != '' && $ip != '0.0.0.0') {
        $string = snmp2_get($ip, 'public', '1.3.6.1.2.1.2.2.1.6.2', 1000000,1);
        if (strlen($string) > 8 ) {
            $string = preg_replace('/ /', '', preg_replace('/"/', '', $string));
            $string = explode(':', $string);
            foreach ($string as $valor) {
                if (strlen($valor) < 2 ) $mac .= '0';
                $mac .= $valor;
            }
            unset($valor);
        }
        unset($string);
    }
    return strtoupper($mac);
}

function convertTimestamp($texto) {
    $texto = preg_replace('/</', '', $texto);
    return substr($texto, 0,4)."-".substr($texto, 4,2)."-".substr($texto, 6,2)." : ".substr($texto, 8,2).":".substr($texto, 10,2).":".substr($texto, 12,2);
}

function convertMacHexDec($cm_mac) {
    $cm_dec[0] = hexdec(substr($cm_mac,0,2));
    $cm_dec[1] = hexdec(substr($cm_mac,2,2));
    $cm_dec[2] = hexdec(substr($cm_mac,4,2));
    $cm_dec[3] = hexdec(substr($cm_mac,6,2));
    $cm_dec[4] = hexdec(substr($cm_mac,8,2));
    $cm_dec[5] = hexdec(substr($cm_mac,10,2));
    $cm_macdecimal = $cm_dec[0].'.'.$cm_dec[1].'.'.$cm_dec[2].'.'.$cm_dec[3].'.'.$cm_dec[4].'.'.$cm_dec[5];
    
    return $cm_macdecimal;
}

function getSnmptableindex($cm_macdecimal, $ip_cmts) {
    
    $retorno[1] = 0;
    $cdxIfCmtsCmStatusValue = "1.3.6.1.4.1.9.9.116.1.3.2.1.1.";
    $docsIfCmtsCmPtr        = "1.3.6.1.2.1.10.127.1.3.7.1.2.";
  
    $cmtsTableIndex = snmpget($ip_cmts, $community_cmts, $docsIfCmtsCmPtr.$cm_macdecimal);
    if($cmtsTableIndex[0]){
        $retorno[0] = $ip_cmts;
        $retorno[1] = $cmtsTableIndex;
    }
   
    return array ($retorno[0], $retorno[1]);
}

function getMib($ip_cmts, $TableIndex) {
    
    $ip_cm = snmpget($ip_cmts, $community_cmts, '1.3.6.1.2.1.10.127.1.3.3.1.3.'.$TableIndex);               // IP do terminal
    $snr_up = (snmpget($ip_cmts, $community_cmts, '1.3.6.1.2.1.10.127.1.3.3.1.13.'.$TableIndex) * 0.1);      // SNR UP
    $porta_up = snmpget($ip_cmts, $community_cmts, '1.3.6.1.2.1.10.127.1.3.3.1.5.'.$TableIndex);            // Porta upstream 
    $porta_down = snmpget($ip_cmts, $community_cmts, '1.3.6.1.2.1.2.2.1.2.'.$porta_up);                      // Porta downstream
    $cmts_desc = snmpget($ip_cmts, $community_cmts, 'SNMPv2-MIB::sysDescr.0');                              // Informação sobre o CMTS
    if($cmts_desc){
        $pos_vendor = (strpos($cmts_desc, 'VENDOR') + 7);
        $vendor = ltrim(substr($cmts_desc,$pos_vendor,6));
        if($vendor == 'ARRIS') $porta_up = ($porta_up - 1);
    }
    $node = snmpget($ip_cmts, $community_cmts, 'iso.3.6.1.2.1.31.1.1.1.18.'.$porta_up);                     // Description Porta 
    $mib[0] = $ip_cm;
    $mib[1] = $snr_up;
    $mib[2] = $porta_up;
    $mib[3] = $porta_down;
    $mib[4] = $node;
    return  array($mib[0], $mib[1], $mib[2], $mib[3], $mib[4]);
}

function getTimeexecutation($start = null) {
    $mtime = microtime();                       // Pega o microtime
    $mtime = explode(' ',$mtime);               // Quebra o microtime
    $mtime = $mtime[1] + $mtime[0];             // Soma as partes montando um valor inteiro
    if ($start == null) {
        return $mtime;                          // Se o parametro não for especificado, retorna o mtime atual
    } else {
        $tempo  = round($mtime - $start, 2);    // Se o parametro for especificado, retorna o tempo de execução
        if ($tempo > 60)
            $tempo = $tempo/60;
        return $tempo;
    }
}

?>