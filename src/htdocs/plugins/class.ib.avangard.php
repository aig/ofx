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

class IB_AVANGARD extends IB
{
  private $_account_list;
  private $_main_page;

  function __construct() {
    parent::__construct("avangard");
    $this->_account_list = null;
  }

  function login($user, $fish) {
    // try to log in.
    $result = $this->curlLogin($user, $fish);

    if (!$result) return false;

    if (!preg_match('/window.location="(faces\/pages\/firstpage\?ticket=.*?)"/', $result, $match)) {
        return false;
    }

    // fetch main IB page.
    $result = $this->curlMain($match[1]);

    $this->_main_page  = iconv('cp1251', 'utf-8', html_entity_decode($result, ENT_COMPAT, 'cp1251'));

    $this->_account_list = array();
    
    if (preg_match_all('/>((?:40817|42307)\d{3}\d{12})/', $this->_main_page, $matches)) {
      foreach ($matches[1] as $account_id) {
        $this->_account_list[] = new BankAccount($account_id);
      }
    }

    return true;
  }

  private function getStateToken($page) {
    if (preg_match('/name="oracle\.adf\.faces\.STATE_TOKEN"\s+value="(\d+)"/', $page, $match)) {
      return $match[1];
    }

    return false;
  }

  public function getAccountBalance($account_id, $inctran = false) {
    if (!preg_match('/' 
                  . $account_id
                  . '.*?"Детальная информация по счету".*?submitForm\(\'f\'\,1\,\{source:\'(.*?)\'\}\).*?"Выписка по счету"/', $this->_main_page, $action_match)) 
    {
      throw new Exception("Unknown bank account: $account_id");
    }

    $source = $action_match[1];

    $state_token = $this->getStateToken($this->_main_page);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/ibAvn/faces/pages/accounts/all_acc.jspx");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "oracle.adf.faces.FORM=f&"
                                        ."oracle.adf.faces.STATE_TOKEN=$state_token&"
                                        ."source=" . urlencode($source));
    $result = $this->curlExec($ch);

    $page  = iconv('cp1251', 'utf-8', html_entity_decode($result, ENT_COMPAT, 'cp1251'));

    if (!preg_match('/' 
                  . $account_id 
                  . '<\/label><\/td><td class="x2p x62"><span style="display:none;">\-?[\d\s]+.\d+<\/span><div><\/div>'
                  . '<label for="f:leftAccTbl:0:selLeftAcc">\s*(\-?[\d\s]+.\d+)</', $page, $match)) 
    {
      return false;
    }

    $info = array();

    $info['balance'] = preg_replace("/\s/", '', $match[1]);

    if (empty($inctran)) {
      return $info;
    }

    // GnuCash does not set <INCTRAN> INCLUDE field to N, when check balance
    if ($inctran == '19700101') {
      return $info;
    }

    $start_date = strftime('%d.%m.%Y', strtotime("$inctran" . "000000"));

    if (!preg_match('/name="f:finishdate" value="(\d+\.\d+\.\d+)"/', $page, $match)) {
      return $info;
    }

    $finish_date = $match[1];

    $regexp = '/' . $account_id . '.*?submitForm\(\\\\\'f\\\\\'\,1\,\{source:\\\\\'(.*?)\\\\\'\}\).*?"Показать"/s';

    if (!preg_match($regexp, $page, $match)) {
      return $info;
    }

    $source = urlencode($match[1]);

    $state_token = $this->getStateToken($page);

    $fields = "f%3Astartdate=$start_date&"
             ."f%3Afinishdate=$finish_date&"
             ."f%3AleftAccTbl%3Aselected=0&"
             ."f%3AleftAccTbl%3ArangeStart=0&"
             ."f%3AleftCardTbl%3A_us=0&"
             ."f%3AleftCardTbl%3A_us=1&"
             ."f%3AleftCardTbl%3ArangeStart=0&"
             ."oracle.adf.faces.FORM=f&oracle.adf.faces.STATE_TOKEN=$state_token&"
             ."source=$source&"
             ."event=&"
             ."f%3AleftCardTbl%3A_sm=";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/ibAvn/faces/pages/accounts/acc_stat.jspx");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    $result = $this->curlExec($ch);

    $page  = iconv('cp1251', 'utf-8', html_entity_decode($result, ENT_COMPAT, 'cp1251'));

    $info['trn_list'] = $this->parseTransactionList($page);

    return $info;
  }

  private function parseTransactionList($page) {
    $transaction_list = array();

    if (!preg_match('/ПРОВЕДЕННЫЕ ПО КАРТСЧЕТУ ОПЕРАЦИИ(.*?)Исходящий остаток средств на картсчете/s', $page, $match)) {
      return $transaction_list;
    }

    // $1: date, $2: ammount, $3: tr_datetime, $4: tr_card, $5: tr_amount, $6: currency, $7: tr_description
    $regexp = '/(\d{2}\.\d{2}\.\d{4})\s*<\/td>\s*<td[^>]+>\s*<\/td>.*?([\d ]+\.\d+).*?Покупка.*?(\d{2}\.\d{2}\.\d{4}\s\d{2}:\d{2}:\d{2}).*?Карта.*?\*(\d{4})\..*?Сумма.*?([\d ]+\.\d+).*?>(...)\..*?>Место\s([^<]+)/s';

    if (preg_match_all($regexp, $match[1], $matches)) {
      for ($i = 0; $i < count($matches[0]); $i++) {
        $transaction = array();
        $transaction['trntype'] = 'POS';
        $transaction['dtposted'] = substr($matches[1][$i], 6, 4) 
                                 . substr($matches[1][$i], 3, 2) 
                                 . substr($matches[1][$i], 0, 2);
        $transaction['trnamt'] = '-' . preg_replace('/\s/', '', $matches[2][$i]);
    	$transaction['card'] = $matches[4][$i];
    	$transaction['orgamount'] = preg_replace('/\s/', '', $matches[5][$i]);
        $transaction['fitid'] = md5($matches[1][$i].$matches[2][$i].$matches[3][$i].$matches[4][$i].$matches[6][$i].$matches[7][$i]);
        $transaction['dtuser'] = $matches[3][$i];
        $transaction['origcurrency'] = $matches[6][$i];
        $transaction['name'] = $matches[7][$i];
        $transaction_list[] = $transaction;
      }
    }

    return $transaction_list;
  }

  function getAccountList() {
    return $this->_account_list;
  }

  private function curlLogin($user, $fish) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.avangard.ru/rus/index.wbp");
    $result = $this->curlExec($ch);

    if (!preg_match('/<INPUT\s+value="login"\s+name="([^\"]+)"/i', $result, $match)) {
      return false;
    }

    $login_name = $match[1];

    $password = decryptPassword($user, CONFIG::getValue('ib.avangard', 'user.password'), $fish);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/client4/afterlogin");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$login_name=login&"
                                        ."65783213-C4EC-46C3-AEF5-172B7C75C400.redirect=%2Frus%2Findex.wbp&"
                                        ."login_v=&"
                                        ."login=$user&"
                                        ."passwd_v=&"
                                        ."passwd=$password&"
                                        ."x=20&y=7");
    $result = $this->curlExec($ch);

    return $result;
  }

  private function curlMain($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/ibAvn/" . $url);
    $result = $this->curlExec($ch);

    return $result;
  }
}
?>