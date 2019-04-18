<?php


namespace Leos\Application\UseCase\Transaction;


use Leos\Application\UseCase\Transaction\Request\Transfer;
use Leos\Application\UseCase\Transaction\Request\Withdrawal as WithdrawalRequest;
use Leos\Application\UseCase\Wallet\GetWalletHandler;
use Leos\Application\UseCase\Wallet\Request\GetWallet;
use Leos\Domain\Payment\Model\Deposit;
use Leos\Domain\Payment\Model\Withdrawal;
use Leos\Domain\Payment\ValueObject\DepositDetails;
use Leos\Domain\Payment\ValueObject\WithdrawalDetails;
use Leos\Domain\Transaction\Exception\InvalidTransactionTypeException;
use Leos\Domain\Transaction\Repository\TransactionRepositoryInterface;
use Leos\Domain\User\Repository\UserRepositoryInterface;

class CreateTransferHandler
{
    /**
     * @var TransactionRepositoryInterface
     */
    private $repository;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var GetWalletHandler
     */
    private $walletQuery;

    public function __construct(
        TransactionRepositoryInterface $repository,
        UserRepositoryInterface $userRepository,
        GetWalletHandler $walletQuery)
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        $this->walletQuery = $walletQuery;
    }

    /**
     * @return array
     * @throws InvalidTransactionTypeException
     */
    public function handle(Transfer $request): array
    {
        $senderWallet = $this->walletQuery->handle(new GetWallet($request->getSenderWalletId()));
        $receiverWallet = $this->walletQuery->handle(new GetWallet($request->getReceiverWalletId()));

        $withdrawal = Withdrawal::create(
            $senderWallet,
            $request->getReal(),
            new WithdrawalDetails($request->getProvider())
        );

        $deposit = Deposit::create(
            $receiverWallet,
            $request->getReal(),
            new DepositDetails($request->getProvider())
        );

        $this->repository->save($deposit);
        $this->repository->save($withdrawal);

        return [
            $senderWallet->walletId()->__toString() => $withdrawal,
            $receiverWallet->walletId()->__toString() => $deposit,
        ];
    }
}


