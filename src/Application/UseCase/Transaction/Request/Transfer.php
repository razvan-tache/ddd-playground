<?php


namespace Leos\Application\UseCase\Transaction\Request;


use Leos\Domain\Money\ValueObject\Currency;
use Leos\Domain\Money\ValueObject\Money;
use Leos\Domain\Payment\Exception\MinDepositAmountException;
use Leos\Domain\Wallet\ValueObject\WalletId;

class Transfer
{
    /** @var WalletId */
    private $senderWalletId;

    /** @var WalletId */
    private $receiverWalletId;

    /** @var Money */
    private $real;

    /** @var string */
    private $provider;

    public function __construct(
        string $senderWalletId,
        string $receiverWalletId,
        float $amount,
        string $currency,
        string $provider
    ) {
        $this->senderWalletId = new WalletId($senderWalletId);
        $this->receiverWalletId = new WalletId($receiverWalletId);
        $this->setReal($amount, new Currency($currency));
        $this->provider = $provider;
    }

    /**
     * @throws MinDepositAmountException
     */
    protected function setReal(float $amount, Currency $currency)
    {
        if (0.00 >= $amount) {

            throw new MinDepositAmountException();
        }

        $this->real = new Money($amount, $currency);
    }
}