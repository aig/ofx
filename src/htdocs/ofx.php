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

if ($ofx->bank_msgs_rq_v1()->isValid()) {
  if ($ofx->bank_msgs_rq_v1()->stmt_trn_rq()->isValid()) {
    $ib->login($user, $fish);
    $info = $ib->getAccountBalance($ofx->bank_msgs_rq_v1()->stmt_trn_rq()->stmt_rq()->bankacctfrom()->acctid());
    $response = responseAccountBalance($ofx, $info, $ofx->bank_msgs_rq_v1()->stmt_trn_rq()->stmt_rq()->inctran()->dtstart());
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

  $message = "<OFX>
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
</SIGNONMSGSRSV1>

<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1001</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<STMTRS>
<CURDEF>$currency</CURDEF>
<BANKACCTFROM>
<BANKID>$org</BANKID>
<ACCTID>$account_id</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
<LEDGERBAL>
<BALAMT>$balance</BALAMT>
<DTASOF>$balance_time</DTASOF>
</LEDGERBAL>
<AVAILBAL>
<BALAMT>$balance</BALAMT>
<DTASOF>$balance_time</DTASOF>
</AVAILBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>";

  return $message;
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