<?php
/*
 * Copyright (c) AIG
 * aignospam at gmail.com
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

require_once('includes/class.config.php');
require_once('plugins/class.ib.php');
require_once('includes/ofx/class.ofx.php');

$raw = $HTTP_RAW_POST_DATA;

$fh = fopen(Config::getValue('ofx', 'log.file'), 'a+');

try {
  $ofx = new OFX($raw);
} catch (Exception $e) {
    fwrite($fh, 'Caught exception: ' .  $e->getMessage() . "\n" . $e->getTraceAsString());
    fclose($fh);
    exit();
}

fwrite($fh, print_r($raw, true));

$user = $ofx->signon_msgs_rq_v1()->son_rq()->userid();
$fish = $ofx->signon_msgs_rq_v1()->son_rq()->userpass();

$ib = new IB_AVANGARD();
// $ib = new IB_RSB();

if ($ofx->bank_msgs_rq_v1()->isValid()) {
  if ($ofx->bank_msgs_rq_v1()->stmt_trn_rq()->isValid()) {
    $ib->login($user, $fish);
    $info = $ib->getAccountBalance($ofx->bank_msgs_rq_v1()->stmt_trn_rq()->stmt_rq()->bankacctfrom()->acctid(), $ofx->bank_msgs_rq_v1()->stmt_trn_rq()->stmt_rq()->inctran()->dtstart());
    fwrite($fh, print_r($info, true));
    $response = responseAccountBalance($ofx, $info);
  }
} elseif ($ofx->signup_msgs_rq_v1()->isValid()) {
  $ib->login($user, $fish);
  $account_list = $ib->getAccountList();
  $response = responseAccountInfo($ofx, $account_list);
} 

echo $response;
fwrite($fh, $response);
fclose($fh);

function responseAccountBalance($ofx, $info) {
  $balance = $info['balance'];

  $system_time = strftime("%Y%m%d%H%M%S");
  $balance_time = strftime("%Y%m%d%H%M");
  $account_id = $ofx->bank_msgs_rq_v1()->stmt_trn_rq()->stmt_rq()->bankacctfrom()->acctid();

  $org = $ofx->signon_msgs_rq_v1()->son_rq()->fi()->org();
 
  $currency_code = substr($account_id, 5, 3);

  $currency = Config::getValue('currency', "code.$currency_code");

  $xml = DOMDocument::load('templates/account.balance.xml');

  $xml->formatOutput = true;

  $sonrs = $xml->documentElement->getElementsByTagName('SONRS')->item(0);

  $sonrs->getElementsByTagName('CODE')->item(0)->nodeValue = 0;
  $sonrs->getElementsByTagName('SEVERITY')->item(0)->nodeValue = "INFO";
  $sonrs->getElementsByTagName('DTSERVER')->item(0)->nodeValue = $system_time;
  $sonrs->getElementsByTagName('DTPROFUP')->item(0)->nodeValue = $system_time;
  $sonrs->getElementsByTagName('DTACCTUP')->item(0)->nodeValue = $system_time;
  $sonrs->getElementsByTagName('LANGUAGE')->item(0)->nodeValue = "ENG";
  $sonrs->getElementsByTagName('ORG')->item(0)->nodeValue = $org;
  $sonrs->getElementsByTagName('FID')->item(0)->nodeValue = $org;

  $stmt_trn_rs = $xml->documentElement->getElementsByTagName('STMTTRNRS')->item(0);
  $stmt_trn_rs->getElementsByTagName('TRNUID')->item(0)->nodeValue = "1";
  $stmt_trn_rs->getElementsByTagName('CODE')->item(0)->nodeValue = "0";
  $stmt_trn_rs->getElementsByTagName('SEVERITY')->item(0)->nodeValue = "INFO";
  $stmt_trn_rs->getElementsByTagName('CURDEF')->item(0)->nodeValue = $currency;
  $stmt_trn_rs->getElementsByTagName('BANKID')->item(0)->nodeValue = $org;
  $stmt_trn_rs->getElementsByTagName('ACCTID')->item(0)->nodeValue = $account_id;
  $stmt_trn_rs->getElementsByTagName('ACCTTYPE')->item(0)->nodeValue = "CHECKING";

  $stmt_trn_rs->getElementsByTagName('BALAMT')->item(0)->nodeValue = $balance;
  $stmt_trn_rs->getElementsByTagName('BALAMT')->item(1)->nodeValue = $balance;

  $stmt_trn_rs->getElementsByTagName('DTASOF')->item(0)->nodeValue = $balance_time;
  $stmt_trn_rs->getElementsByTagName('DTASOF')->item(1)->nodeValue = $balance_time;

  $stmt_rs = $stmt_trn_rs->getElementsByTagName('STMTRS')->item(0);
  
  $bank_tran_list = $xml->createElement('BANKTRANLIST');
  
  $bank_tran_list->appendChild(new DOMElement('DTSTART', '20100908'));
  $bank_tran_list->appendChild(new DOMElement('DTEND', '20100908'));

  foreach ($info['trn_list'] as $trn) {
    $stmt_trn = $xml->createElement('STMTTRN');
    $stmt_trn->appendChild(new DOMElement('TRNTYPE', $trn['trntype']));
    $stmt_trn->appendChild(new DOMElement('TRNTYPE', $trn['trntype']));
    $stmt_trn->appendChild(new DOMElement('DTPOSTED', $trn['dtposted']));
    $stmt_trn->appendChild(new DOMElement('TRNAMT', $trn['trnamt']));
    $stmt_trn->appendChild(new DOMElement('FITID', $trn['fitid']));
    $stmt_trn->appendChild(new DOMElement('NAME', $trn['name']));
    $stmt_trn->appendChild(new DOMElement('MEMO', '*' . $trn['card'] . ' ' . $trn['dtuser']));
    $bank_tran_list->appendChild($stmt_trn);
  }

  $stmt_rs->appendChild($bank_tran_list);
  
  return $xml->saveXML($xml->documentElement);
}

function responseAccountInfo($ofx, $account_list) {
  $system_time = strftime("%Y%m%d%H%M%S");
  $org = $ofx->signon_msgs_rq_v1()->son_rq()->fi()->org();

  $message = "
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>$system_time</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
<DTPROFUP>$system_time</DTPROFUP>
<DTACCTUP>$system_time</DTACCTUP>
<FI>
<ORG>$org</ORG>
<FID>$org</FID>
</FI>
</SONRS>
</SIGNONMSGSRSV1>";

  $message .= "
<BANKMSGSRSV1>
<ACCTINFOTRNRS>
<TRNUID>12345</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<ACCTINFORS>
<DTACCTUP>20050301</DTACCTUP>";
  
  foreach ($account_list as $account) {
    $account_id = $account->id();
    $message .= "
<ACCTINFO>
<DESC>Checking</DESC>
<PHONE>88003339898</PHONE>
<BANKACCTINFO>
<BANKACCTFROM>
<BANKID>$org</BANKID>
<ACCTID>$account_id</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
<SUPTXDL>Y</SUPTXDL>
<XFERSRC>Y</XFERSRC>
<XFERDEST>Y</XFERDEST>
<SVCSTATUS>ACTIVE</SVCSTATUS>
</BANKACCTINFO>
</ACCTINFO>";
  }

  $message .= "</ACCTINFORS></ACCTINFOTRNRS></BANKMSGSRSV1></OFX>";
  return $message;
}
?>