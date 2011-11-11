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

class IB_RSB extends IB {
    private $_account_list;

    public function __construct() {
        parent::__construct("rsb");
        $this->_account_list = null;
    }

    public function login($user, $fish) {
        // try to log in.
        $result = $this->curlLogin($user, $fish);

        if (!$result) return false;

        if (!preg_match('/submitForm\(\'mainform\'\,1\,\{source:\'([^\']+)\'\}\)[^>]+>' . mb_convert_encoding('реквизиты счетов', 'HTML-ENTITIES', 'UTF-8') . '/', $result, $match)) {
            return false;
        }

        $source = $match[1];

        $state_token = $this->getStateToken($result);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.rsb.ru/hb/faces/rs/RSIndex.jspx");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "oracle.adf.faces.FORM=mainform&oracle.adf.faces.STATE_TOKEN={$state_token}&source=" . urlencode($source));

        $result = $this->curlExec($ch);

        if (!preg_match('/submitForm\(\'mainform\'\,1\,\{source:\'([^\']+)\'\}\)[^>]+>' . mb_convert_encoding('Пополняемые счета', 'HTML-ENTITIES', 'UTF-8') . '/', $result, $match)) {
            return false;
        }

        $source = $match[1];

        $state_token = $this->getStateToken($result);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.rsb.ru/hb/faces/rs/RSIndex.jspx");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "oracle.adf.faces.FORM=mainform&oracle.adf.faces.STATE_TOKEN={$state_token}&source=" . urlencode($source));

        $result = $this->curlExec($ch);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.rsb.ru/hb/ReportServlet");

        $result = $this->curlExec($ch);

        $this->_account_list = array();

        if (preg_match_all('/>((?:40817|42307|42306)\d{3}\d{12})/', $result, $matches)) {
            foreach ($matches[1] as $account_id) {
            $this->_account_list[] = new BankAccount($account_id);
          }
        }

        return true;
    }

    private function getStateToken($page) {
        if (preg_match('/value="(\d+)"\s+name="oracle\.adf\.faces\.STATE_TOKEN"/', $page, $match)) {
            return $match[1];
        }

        if (preg_match('/name="oracle\.adf\.faces\.STATE_TOKEN"\s+value="(\d+)"/', $page, $match)) {
            return $match[1];
        }

        return false;
    }

    public function getAccountList() {
        return $this->_account_list;
    }

    public function getAccountBalance($account_id, $inctran = false) {
        $info = array();
        $info['balance'] = 0;

        return $info;
    }

    private function curlLogin($user, $fish) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.rsb.ru/hb/faces/system/rslogin.jsp");
        $result = $this->curlExec($ch);

        $password = decryptPassword($user, CONFIG::getValue('ib.rsb', 'user.password'), $fish);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.rsb.ru/hb/faces/security_check");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "j_password={$password}&j_username={$user}&systemid=hb");

        $result = $this->curlExec($ch);

        return $result;
    }
}
?>
