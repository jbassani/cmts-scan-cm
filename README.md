# Scanear terminais em CMTS
> Script para coletar lista de terminais sincronizados em um equipamento CMTS e posteriormente coletar informações de cada terminal.

**Pré-requisitos**
- <a href="https://www.php.net/">PHP</a>
- <a href="http://www.net-snmp.org/">Net-SNMP</a>

**Configuração CMTS**
> CMTS deve estar habilitado para consulta SNMP. Consulte o manual do fabricante para habilitar esta função.
> Deve ser configurado os fibernodes para sincronismo dos terminais

**Configuração do ambiente no Linux - Ubuntu**
> sudo apt install php php-snmp

**Configuração do ambiente no Linux - CentOS**
> sudo yum install php php-snmp

**Execução do script**
- php scan.php <IP DO CMTS> <COMUNIDADE SNMP DO CMTS>
