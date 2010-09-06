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

class OFX_TRN_RQ extends OFX_OBJECT
{
  private $_trnuid;
  private $_cltcookie;
  private $_tan;

  public function __construct($name, $raw) {
    parent::__construct($name, $raw);

    // Client-assigned globally-unique ID for this transaction, trnuid
    $this->_trnuid = $this->requiredTag("TRNUID");

    // Data to be echoed in the transaction response, A-32
    $this->_cltcookie = $this->optionalTag("CLTCOOKIE");

    // Transaction authorization number; used in some countries with some types of transactions.
    // The FI Profile defines messages that require a <TAN>, A-80
    $this->_tan = $this->optionalTag("TAN");
  }

  public function trnuid() { return $this->_trnuid; }
  public function cltcookie() { return $this->_cltcookie; }
  public function tan() { return $this->_tan; }
}
?>
