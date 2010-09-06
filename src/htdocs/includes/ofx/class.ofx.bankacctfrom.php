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

class OFX_BANKACCTFROM extends OFX_OBJECT
{
  private $_bankid;
  private $_branchid;
  private $_acctid;
  private $_accttype;

  public function __construct($raw) {
    parent::__construct("BANKACCTFROM", $raw);

    // Bank identifier, A-9
    $this->_bankid = $this->requiredTag("BANKID");

    // Branch identifier. May be required for some non-US banks, A-22
    $this->_branch = $this->optionalTag("BRANCHID");

    // Account number, A-22
    $this->_acctid = $this->requiredTag("ACCTID");

    // Type of account, see section 11.3.1.1
    $this->_accttype = $this->requiredTag("ACCTTYPE");

    // Checksum, A-22
    $this->_acctkey = $this->optionalTag("ACCTKEY");
  }

  public function bankid() { return $this->_bankid; }
  public function branch() { return $this->_branch; }
  public function acctid() { return $this->_acctid; }
  public function accttype() { return $this->_accttype; }
  public function acctkey() { return $this->_acctkey; }
}
?>
