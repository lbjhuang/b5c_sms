<?php

define('SCRIPT_ROOT', dirname(__FILE__).'/');
require_once SCRIPT_ROOT . 'config.php';


$oci_conn = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = " .OCI_HOST. ")(PORT = 1521)))(CONNECT_DATA=(SID=ECOLOGY)))";
$oci_db = oci_connect( OCI_USER, OCI_PWD, $oci_conn, 'UTF8' );

$link = mysql_connect( DB_HOST, DB_USER, DB_PWD );
mysql_select_db( DB_NAME, $link );
mysql_query('set names utf8');

//$sql = "SELECT tcss.SP_NAME, tcss.SP_CHARTER_NO, tmfa.RISK_RATING FROM `tb_crm_sp_supplier` tcss LEFT JOIN `tb_ms_forensic_audit` tmfa ON tmfa.SP_CHARTER_NO = tcss.SP_CHARTER_NO ORDER BY tcss.CREATE_TIME DESC";
$sql = "SELECT SP_NAME,SP_CHARTER_NO,RISK_RATING FROM `tb_crm_sp_supplier`";
$result = mysql_query($sql, $link);

while( $res = mysql_fetch_assoc( $result) ) {
	$company_name = $res['SP_NAME'];
	$charter = $res['SP_CHARTER_NO'];
	$risk = $res['RISK_RATING'];
	if( !$company_name ) { continue; }
	$oci_chk_sql = "SELECT \"ID\" FROM \"MMP\" WHERE \"COMPANY\" = '" .$company_name. "'";
        $oci_chk_query = oci_parse($oci_db, $oci_chk_sql);
        oci_execute( $oci_chk_query );
        $res = oci_fetch_row( $oci_chk_query );
        if ( $res ) {
                $oci_sql = "UPDATE \"MMP\" SET \"BUSINESSLICENSE\"='" .$charter. "', \"RISK_RATING\"='" .$risk. "' WHERE \"COMPANY\" = '" .$company_name. "'";
        } else {
                $oci_sql = "INSERT INTO MMP (COMPANY, BUSINESSLICENSE, RISK_RATING) VALUES ('" .$company_name. "', '" .$charter. "', '" .$risk. "')";
        }
	echo $oci_sql . "\n";
	$oci_query = oci_parse( $oci_db, $oci_sql );
        oci_execute( $oci_query );
}
mysql_close( $link );
oci_close( $oci_db );

file_put_contents( LOGPATH . "sms2oci.log", date('Y-m-d H:i:s') . " Success\n", FILE_APPEND);