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

class OFX_SON_RQ extends OFX_OBJECT
{
  private $_userid;
  private $_userpass;
  private $_language;
  private $_appid;
  private $_appver;
  private $_fi;

  public function __construct($raw) {
    parent::__construct('SONRQ', $raw);

    $this->_userid = $this->requiredTag('USERID');
    $this->_userpass = $this->requiredTag('USERPASS');
    $this->_language = $this->requiredTag('LANGUAGE');
    $this->_appid = $this->requiredTag('APPID');
    $this->_appver = $this->requiredTag('APPVER');

    $this->_fi = new OFX_FI($this->raw());
  }

 function userid() { return $this->_userid; }
 function userpass() { return $this->_userpass; }
 function language() { return $this->_language; }
 function appid() { return $this->_appid; }
 function appver() { return $this->_appver; }
 function fi() { return $this->_fi; }
}
?>
