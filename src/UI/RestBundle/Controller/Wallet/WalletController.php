<?php

namespace Leos\UI\RestBundle\Controller\Wallet;

use Leos\Application\UseCase\Transaction\Request\Transfer;
use Leos\Domain\Payment\Model\Deposit;
use Leos\Domain\Wallet\ValueObject\WalletId;
use Leos\Infrastructure\CommonBundle\Exception\Form\FormException;
use Leos\UI\RestBundle\Controller\AbstractBusController;

use Leos\Application\UseCase\Wallet\Request\Find;
use Leos\Application\UseCase\Wallet\Request\GetWallet;
use Leos\Application\UseCase\Transaction\Request\CreateDeposit;
use Leos\Application\UseCase\Transaction\Request\Withdrawal;
use Leos\Application\UseCase\Transaction\Request\CreateWallet;


use Leos\Domain\Wallet\Model\Wallet;
use Leos\Domain\Payment\Model\Withdrawal as WithdrawalModel;
use Leos\Infrastructure\CommonBundle\Pagination\PagerTrait;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Hateoas\Representation\PaginatedRepresentation;

use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Symfony\Component\Form\Form;

/**
 * Class WalletController
 *
 * @package Leos\UI\RestBundle\Controller\Wallet
 *
 * @RouteResource("Wallet", pluralize=false)
 */
class WalletController extends AbstractBusController
{
    use PagerTrait;

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "List wallet collection",
     *     output = "Leos\Domain\Wallet\Model\Wallet",
     *     statusCodes = {
     *       201 = "Returned when successful",
     *       400 = "Returned when Bad Request",
     *       404 = "Returned when page not found"
     *     }
     * )
     *
     * @QueryParam(
     *     name="page",
     *     default="1",
     *     description="Page Number"
     * )
     * @QueryParam(
     *     name="limit",
     *     default="500",
     *     description="Items per page"
     * )
     *
     * @QueryParam(
     *     name="orderParameter",
     *     nullable=true,
     *     requirements="(real.amount|bonus.amount|createdAt|updatedAt)",
     *     map=true,
     *     description="Order Parameter"
     * )
     *
     * @QueryParam(
     *     name="orderValue",
     *     nullable=true,
     *     requirements="(ASC|DESC)",
     *     map=true,
     *     description="Order Value"
     * )
     *
     * @QueryParam(
     *     name="filterParam",
     *     nullable=true,
     *     requirements="(real.amount|bonus.amount|createdAt|updatedAt)",
     *     strict=true,
     *     map=true,
     *     description="Keys to filter"
     * )
     *
     * @QueryParam(
     *     name="filterOp",
     *     nullable=true,
     *     requirements="(gt|gte|lt|lte|eq|like|between)",
     *     strict=true,
     *     map=true,
     *     description="Operators to filter"
     * )
     *
     * @QueryParam(
     *     name="filterValue",
     *     map=true,
     *     description="Values to filter"
     * )
     *
     * @View(statusCode=200, serializerGroups={"Default", "Identifier", "Basic"})
     *
     * @param ParamFetcher $fetcher
     *
     * @return PaginatedRepresentation
     */
    public function cgetAction(ParamFetcher $fetcher): PaginatedRepresentation
    {
        $request = new Find($fetcher->all());

        return $this->getPagination(
            $this->ask($request),
            'cget_wallet',
            [],
            $request->getLimit(),
            $request->getPage()
        );
    }

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "Gets a wallet for the given identifier",
     *     output = "Leos\Domain\Wallet\Model\Wallet",
     *     statusCodes = {
     *       200 = "Returned when successful",
     *       404 = "Returned when not found"
     *     }
     * )
     *
     * @View(statusCode=200, serializerGroups={"Identifier", "Basic"})
     *
     * @param string $walletId
     *
     * @return Wallet
     */
    public function getAction(string $walletId): Wallet
    {
        return $this->ask(new GetWallet($walletId));
    }

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "Create a new Wallet",
     *     output = "Leos\Domain\Wallet\Model\Wallet",
     *     statusCodes = {
     *       201 = "Returned when successful"
     *     }
     * )
     *
     * @RequestParam(name="userId",   default="none", description="The user identifier")
     * @RequestParam(name="currency", default="EUR",  description="The currency of the wallet")
     *
     * @View(statusCode=201)
     *
     * @param ParamFetcher $fetcher
     *
     * @return \FOS\RestBundle\View\View|Form
     */
    public function postAction(ParamFetcher $fetcher)
    {
        try {
            /** @var Wallet $wallet */
            $wallet = $this->handle(
                new CreateWallet(
                    $fetcher->get('userId'),
                    $fetcher->get('currency')
                )
            );
        } catch (FormException $exception) {

            return $exception->getForm();
        }

        return $this->routeRedirectView('get_wallet', [ 'walletId' => $wallet->id() ]);
    }

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "Generate a positive insertion on the given Wallet",
     *     output = "Leos\Domain\Debit\Model\Debit",
     *     statusCodes = {
     *       202 = "Returned when successful",
     *       400 = "Returned when bad request",
     *       404 = "Returned when wallet not found"
     *     }
     * )
     *
     * @RequestParam(name="real",     default="0",   description="Deposit amount")
     * @RequestParam(name="currency", default="EUR", description="Currency")
     * @RequestParam(name="provider", default="", description="Payment provider")
     *
     * @View(statusCode=202, serializerGroups={"Identifier", "Basic"})
     *
     * @param string $uid
     * @param ParamFetcher $fetcher
     *
     * @return Deposit
     */
    public function postDepositAction(string $uid, ParamFetcher $fetcher): Deposit
    {
        return $this->handle(
            new CreateDeposit(
                $uid,
                $fetcher->get('currency'),
                (float) $fetcher->get('real'),
                $fetcher->get('provider')
            )
        );
    }

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "Generate a negative insertion on the given Wallet",
     *     output = "Leos\Domain\Payment\Model\Withdrawal",
     *     statusCodes = {
     *       202 = "Returned when successful",
     *       400 = "Returned when bad request",
     *       404 = "Returned when wallet not found",
     *       409 = "Returned when not enough founds"
     *     }
     * )
     *
     * @RequestParam(name="real",     default="0",  description="Withdrawal amount")
     * @RequestParam(name="currency", default="EUR", description="Currency")
     * @RequestParam(name="provider", default="", description="Payment provider")
     *
     * @View(statusCode=202, serializerGroups={"Identifier", "Basic"})
     *
     * @param string $uid
     * @param ParamFetcher $fetcher
     *
     * @return WithdrawalModel
     */
    public function postWithdrawalAction(string $uid, ParamFetcher $fetcher): WithdrawalModel
    {
        return $this->handle(
            new Withdrawal(
                $uid,
                $fetcher->get('currency'),
                (float) $fetcher->get('real'),
                $fetcher->get('provider')
            )
        );
    }

    /**
     * @ApiDoc(
     *     resource = true,
     *     section="Wallet",
     *     description = "Transfer from a wallet to another wallet",
     *     output = "Leos\Domain\Payment\Model\Withdrawal",
     *     statusCodes = {
     *       202 = "Returned when successful",
     *       400 = "Returned when bad request",
     *       404 = "Returned when wallet not found",
     *       409 = "Returned when not enough founds"
     *     }
     * )
     *
     * @RequestParam(name="receiverWalletUuid", default="0",  description="Receiver wallet uuid")
     * @RequestParam(name="real",               default="0",  description="Withdrawal amount")
     * @RequestParam(name="currency",           default="EUR", description="Currency")
     * @RequestParam(name="provider",           default="", description="Payment provider")
     *
     * @View(statusCode=202, serializerGroups={"Identifier", "Basic"})
     *
     * @param string $uid
     * @param ParamFetcher $fetcher
     *
     * @return null
     */
    public function postTransferAction(string $uid, ParamFetcher $fetcher)
    {
        new Transfer(
            $uid,
            $fetcher->get("receiverWalletUuid"),
            $fetcher->get("real")
        );

        return null;
    }
}
