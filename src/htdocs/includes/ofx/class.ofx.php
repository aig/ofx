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

require_once('class.ofx.object.php');
require_once('class.ofx.son.rq.php');
require_once('class.ofx.fi.php');
require_once('class.ofx.signon.msgs.rq.v1.php');
require_once('class.ofx.signup.msgs.rq.v1.php');
require_once('class.ofx.bank.msgs.rq.v1.php');
require_once('class.ofx.trn.rq.php');
require_once('class.ofx.acctinfo.trn.rq.php');
require_once('class.ofx.stmt.trn.rq.php');
require_once('class.ofx.stmt.rq.php');
require_once('class.ofx.acctinfo.rq.php');
require_once('class.ofx.bankacctfrom.php');
require_once('class.ofx.inctran.php');

class OFX extends OFX_OBJECT
{
  private $_signon_mgsgs_rq_v1;
  private $_signup_mgsgs_rq_v1;
  private $_bank_msgs_rq_v1;

  public function __construct($raw) {
    parent::__construct('OFX', $raw);

    if ($this->isNull()) {
      throw new Exception("<OFX> is required.");
    }

    $this->_signon_msgs_rq_v1 = new OFX_SIGNON_MSGS_RQ_V1($this->raw());
    $this->_signup_msgs_rq_v1 = new OFX_SIGNUP_MSGS_RQ_V1($this->raw());
    $this->_bank_msgs_rq_v1 = new OFX_BANK_MSGS_RQ_V1($this->raw());
  }

  public function bank_msgs_rq_v1() { return $this->_bank_msgs_rq_v1; }
  public function signon_msgs_rq_v1() { return $this->_signon_msgs_rq_v1; }
  public function signup_msgs_rq_v1() { return $this->_signup_msgs_rq_v1; }
}
?>
