<?php

require_once __DIR__ . '/../classes/Candidate.php';
require_once __DIR__ . '/../classes/Offer.php';

class OfferController
{
    private $candidateModel;
    private $offerModel;

    public function __construct()
    {
        $this->candidateModel = new Candidate();
        $this->offerModel = new Offer();
    }

    public function showOfferForm()
    {
        $applications = $this->candidateModel->getCandidatesWithOfferStatus();

        require_once __DIR__ . '/../offer_salary.php';
    }

    public function acceptOffer()
    {
        $offer_id = $_POST['offer_id'] ?? 0;

        if (!$offer_id) {
            $_SESSION['error'] = "Invalid offer.";
            header("Location: index.php?page=offered");
            exit;
        }

        // Update offer status
        $this->offerModel->updateStatus($offer_id, 'Accepted');

        // Get offer details
        $offer = $this->offerModel->getOfferById($offer_id);

        if (
            $offer &&
            !$this->offerModel->alreadyHired($offer['application_id'])
        ) {
            $this->offerModel->insertHired($offer);
        }

        $_SESSION['success'] = "Offer accepted and candidate hired!";

        header("Location: index.php?page=offered");
        exit;
    }


    public function hireApplicant()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=offered-list");
            exit;
        }

        try {

            $offerId = (int) ($_POST['offer_id'] ?? 0);

            if ($offerId <= 0) {
                throw new Exception("Invalid offer ID.");
            }

            $this->offerModel->hireApplicant($offerId);

            $_SESSION['success'] =
                "Applicant successfully hired and added to the employee list.";

            header("Location: index.php?page=offered-list");
            exit;
        } catch (Throwable $e) {

            $_SESSION['error'] = $e->getMessage();

            header("Location: index.php?page=offered-list");
            exit;
        }
    }
}
