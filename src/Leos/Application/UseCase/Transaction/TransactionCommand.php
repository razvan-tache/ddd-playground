<?php

namespace Leos\Application\UseCase\Transaction;

use Leos\Application\DTO\Deposit\DepositDTO;
use Leos\Application\DTO\Deposit\RollbackDepositDTO;
use Leos\Application\DTO\Wallet\CreateWalletDTO;
use Leos\Application\DTO\Withdrawal\RollbackWithdrawalDTO;
use Leos\Application\DTO\Withdrawal\WithdrawalDTO;
use Leos\Application\UseCase\Wallet\WalletQuery;

use Leos\Domain\Deposit\Model\RollbackDeposit;
use Leos\Domain\Wallet\Model\Wallet;
use Leos\Domain\Deposit\Model\Deposit;
use Leos\Domain\Withdrawal\Model\RollbackWithdrawal;
use Leos\Domain\Withdrawal\Model\Withdrawal;
use Leos\Domain\Wallet\Factory\WalletFactory;

use Leos\Domain\Transaction\Repository\TransactionRepositoryInterface;

/**
 * Class TransactionCommand
 *
 * @package Leos\Application\UseCase\Wallet
 */
class TransactionCommand
{
    /**
     * @var TransactionRepositoryInterface
     */
    private $repository;

    /**
     * @var WalletQuery
     */
    private $walletQuery;

    /**
     * TransactionCommand constructor.
     *
     * @param TransactionRepositoryInterface $repository
     * @param WalletQuery $walletQuery
     */
    public function __construct(TransactionRepositoryInterface $repository, WalletQuery $walletQuery)
    {
        $this->repository = $repository;
        $this->walletQuery = $walletQuery;
    }

    /**
     * @param WithdrawalDTO $dto
     * @return Withdrawal
     */
    public function withdrawal(WithdrawalDTO $dto): Withdrawal
    {
        $transaction = new Withdrawal(
            $this->walletQuery->get($dto->walletId()),
            $dto->real()
        );

        $this->repository->save($transaction);

        return $transaction;
    }

    /**
     * @param RollbackWithdrawalDTO $dto
     * @return RollbackWithdrawal
     */
    public function rollbackWithdrawal(RollbackWithdrawalDTO $dto): RollbackWithdrawal
    {
        $transaction = new RollbackWithdrawal(
            $this->repository->get($dto->withdrawalId())
        );

        $this->repository->save($transaction);

        return $transaction;
    }

    /**
     * @param DepositDTO $dto
     * @return Deposit
     */
    public function deposit(DepositDTO $dto): Deposit
    {
        $transaction = new Deposit(
            $this->walletQuery->get($dto->walletId()),
            $dto->real()
        );

        $this->repository->save($transaction);

        return $transaction;
    }

    /**
     * @param RollbackDepositDTO $dto
     *
     * @return RollbackDeposit
     */
    public function rollbackDeposit(RollbackDepositDTO $dto): RollbackDeposit
    {
        $transaction = new RollbackDeposit(
            $this->repository->get($dto->depositId())
        );

        $this->repository->save($transaction);

        return $transaction;
    }

    /**
     * @param CreateWalletDTO $dto
     * @return Wallet
     */
    public function createWallet(CreateWalletDTO $dto): Wallet
    {
        $transaction = new WalletFactory($dto->currency());

        $this->repository->save($transaction);

        return $transaction->wallet();
    }
}