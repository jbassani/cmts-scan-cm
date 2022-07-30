<?php 
/* 
 *  COLETA LISTA DE TERMINAIS SINCRONIZADOS NO CMTS
 *  COLETA INFORMAÇÕES SOBRE O TERMINAL E NÍVEL DE SINAL
 *  RETORNA RESULTADO EM JSON
 */

require_once "./function/function.php";

if (!isset($argv[1]) && !isset($argv[2]))  
{
    echo "Para executar o script execute o comando abaixo:";
    echo "php ".$argv[0]." IP-DO-CTMS COMMUNITY-SNMP";
    exit();
}

define('mTIME', getTimeexecutation());
$mTIME = getTimeexecutation();
$debug = 1;                                 // Habilita/Desabilita Debug: 0 - FALSE; 1 - TRUE
$modo_execucao = "\n";                      // Utilizado para quebrar linha quando debug estiver ativo: \n em CLI e <BR> em HTTP
$resultado = array();                       // Array com o conteudo coletado
$ip_cmts = $argv[1];                        // IP do CMTS informado no parâmetro do script
$community_cmts = $argv[2];                 // Community SNMP do CMTS informado no parâmetro do script
$oid_ip = '1.3.6.1.2.1.10.127.1.3.3.1.3';   // Oid
$oid_desc = 'IF-MIB::ifDescr.';             // Oid
$oid = "1.3.6.1.2.1.10.127.1.1.4.1.5.";     // Oid
$community_cm = 'public';                   // Community SNMP dos terminais
$total_terminal_degradacao = 0;             // Flag para contabilizar total de equipamentos com degradação em portadoras DS
$problema_degradacao = 0;                   // Flag pra selecionar terminais com degradação no direto
$arquivo = './lista_terminais.json'         // Caminho e nome do arquivo a ser salvo com o conteúdo da coleta

snmp_set_quick_print(1);


$cm_ip = snmpwalk($ip_cmts, $community_cmts, $oid_ip);  // Consulta lista de IPs conectados no CMTS
$linha = array();
        
for($x=0; $x < count($cm_ip) ; $x++)
//for($x=0; $x < 15; $x++)
{        
    $ip = $cm_ip[$x];
    $mac = getMac($ip);
    
    if ($ip == '0.0.0.0' || $mac == '' || strlen($mac) < 8)
    {
        if ($debug == 1)  echo $x.". CM não encontrado (IP: ".$ip." / MAC: ".$mac.")".$modo_execucao;
    }
    else
    {
        $rx  = (snmpget($ip, 'public', '1.3.6.1.2.1.10.127.1.1.1.1.6.3',1000000,1) * 0.1);          // Sinal RX
        if ($rx == null) $rx=0; else $rx = number_format($rx,2);
        
        $tx  = (snmpget($ip, 'public', '1.3.6.1.2.1.10.127.1.2.2.1.3.2',1000000,1) * 0.1);          // Sinal TX
        if ($tx == null) $tx=0; else $tx = number_format($tx,2);
        
        $cm_desc = snmp2_get($ip, 'public', '1.3.6.1.2.1.1.1.0');                                   // Informação do terminal
        $cm_desc = preg_replace('/</', '', $cm_desc);

        if($cm_desc)
        {
            $retorno = explode(";",$cm_desc);
            foreach ($retorno as $val )
            {
                if ( strpos($val, 'VENDOR') ) $vendor = preg_replace('/>/', '', substr($val,strpos($val, 'VENDOR')+8,strlen($val)) );
                if ( strpos($val, 'MODEL') ) $modelo = preg_replace('/>/', '', substr($val,strpos($val, 'MODEL')+7,10) );
                if ( strpos($val, 'SW_REV')) $versao = substr($val,strpos($val, 'SW_REV')+8, strlen($val));
            }
        }
        unset($cm_desc);
        
        if ($debug == 1) echo $x.$modo_execucao; 

        $frequencia_portads = snmpwalk($ip, 'public', '1.3.6.1.2.1.10.127.1.1.1.1.2');
        $mer_portads = snmpwalk($ip, 'public', '1.3.6.1.2.1.10.127.1.1.4.1.5'); 

        for($y=0;$y < count($mer_portads); $y++)
        {
            if ($mer_portads[$y] > 0 && $mer_portads[$y] < 350) 
            {
                $problema_degradacao=1;
                $total_terminal_degradacao++;
            }
            //Passa valor do SNR para decimal
            $mer_portads[$y] = number_format($mer_portads[$y] /10,2);
            $frequencia_portads[$y] = str_replace('000000','',$frequencia_portads[$y]);
        }  

        $linha = array('Data: ' => date('Y-m-d H:i:s'), 'Problema' => $problema_degradacao, 'IP: ' => $ip, 'MAC: ' => $mac, 'Vendor: ' => $vendor, 'Modelo:' => $modelo, 'Versao: ' => $versao, 'Frequencia Direto' => $frequencia_portads, 'Mer Direto: ' => $mer_portads, 'TX: ' => $tx, 'RX: ' => $rx );
        array_push($resultado,$linha);
                
        unset($node);
        unset($mac);
        unset($modelo);
        unset($versao);
        unset($vendor);
        unset($rx);
        unset($tx);
        unset($mer_portads);
        unset($frequencia_portads);
        unset($cm_desc);
        unset($problema_degradacao);
        
    } //CM encontrado    
}

if ($debug == 1) echo json_encode($resultado).$modo_execucao;

//SALVA CONTEUDO NO ARQUIVO
$conteudo = json_encode($resultado);
file_put_contents($arquivo, $conteudo);
$conteudo = json_decode(file_get_contents($arquivo), TRUE);

echo "------------------------------------------------------------------------------------------------".$modo_execucao;
echo 'Total de equipamentos scaneados: ' .count($resultado).$modo_execucao;
echo 'Total de terminais com degradacao no direto: '.$total_terminal_degradacao.$modo_execucao;
echo 'Tempo de carregamento: '.getTimeexecutation(mTIME).$modo_execucao;
?>
