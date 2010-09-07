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

  public function getAccountBalance($account_id) {
    if (!preg_match('/' . $account_id . '.*?"Детальная информация по счету".*?submitForm\(\'f\'\,1\,\{source:\'(.*?)\'\}\).*?"Выписка по счету"/', $this->_main_page, $action_match)) {
      throw Exception("Unknown bank account: $account_id");
    }

    $source = $action_match[1];

    $user_agent = Config::getValue('ib.avangard', 'user.agent');
    $cookie_file = Config::getValue('ib.avangard', 'cookie.file');

    $state_token = $this->getStateToken($this->_main_page);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/ibAvn/faces/pages/accounts/all_acc.jspx");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "oracle.adf.faces.FORM=f&oracle.adf.faces.STATE_TOKEN=$state_token&source=" . urlencode($source));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    curl_close($ch);

    $page  = iconv('cp1251', 'utf-8', html_entity_decode($result, ENT_COMPAT, 'cp1251'));

    if (!preg_match('/' . $account_id . '<\/label><\/td><td class="x2p x62"><span style="display:none;">\-?[\d\s]+.\d+<\/span><div><\/div><label for="f:leftAccTbl:0:selLeftAcc">\s*(\-?[\d\s]+.\d+)</', $page, $balance_match)) {
      return false;
    }

    $balance = preg_replace("/\s/", '', $balance_match[1]);

    return $balance;
  }

  function getAccountList() {
    return $this->_account_list;
  }

  private function curlLogin($user, $fish) {
    $ch = curl_init();

    $user_agent = Config::getValue('ib.avangard', 'user.agent');
    $cookie_file = Config::getValue('ib.avangard', 'cookie.file');

    curl_setopt($ch, CURLOPT_URL, "http://www.avangard.ru/rus/index.wbp");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($ch);
    
    curl_close($ch);

    if (!preg_match('/<INPUT\s+value="login"\s+name="([^\"]+)"/i', $result, $match)) {
      return false;
    }

    $login_name = $match[1];

    $password = decryptPassword($user, CONFIG::getValue('ib.avangard', 'user.password'), $fish);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/client4/afterlogin");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$login_name=login&65783213-C4EC-46C3-AEF5-172B7C75C400.redirect=%2Frus%2Findex.wbp&login_v=&login=$user&passwd_v=&passwd=$password&x=20&y=7");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
  }

  private function curlMain($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://www.avangard.ru/ibAvn/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, Config::getValue('ib.avangard', 'user.agent'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, Config::getValue('ib.avangard', 'cookie.file'));
    curl_setopt($ch, CURLOPT_COOKIEFILE, Config::getValue('ib.avangard', 'cookie.file'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
  }
}
?>