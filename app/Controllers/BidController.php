<?php

namespace App\Controllers;

use App\Models\BidModel;
use App\Models\FeaturedWinnerModel;
use Config\Database;

class BidController extends BaseController
{
    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId   = (int) session()->get('user_id');
        $bidModel = new BidModel();
        $today    = date('Y-m-d');

        $myBidToday = $bidModel
            ->where('user_id', $userId)
            ->where('bid_date', $today)
            ->first();

        // Determine win/lose status without revealing the top bid amount (blind bidding)
        $topBid = $bidModel
            ->where('bid_date', $today)
            ->where('is_active', 1)
            ->orderBy('bid_amount', 'DESC')
            ->first();

        $winningStatus = 'No bid placed yet for today.';
        if ($myBidToday && $topBid) {
            $winningStatus = ((int) $topBid['user_id'] === $userId)
                ? 'You are currently winning!'
                : 'You are currently losing. Consider increasing your bid.';
        }

        $winsThisMonth        = $this->winsThisMonth($userId);
        $allowedWinsThisMonth = $this->allowedWinsThisMonth($userId);

        return view('bids/index', [
            'bids'                 => $bidModel->where('user_id', $userId)->orderBy('id', 'DESC')->findAll(),
            'myBidToday'           => $myBidToday,
            'winningStatus'        => $winningStatus,
            'winsThisMonth'        => $winsThisMonth,
            'allowedWinsThisMonth' => $allowedWinsThisMonth,
        ]);
    }

    public function add()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId = (int) session()->get('user_id');
        $amount = (float) $this->request->getPost('bid_amount');

        if ($amount <= 0) {
            return redirect()->back()->with('error', 'Enter a valid bid amount greater than zero.');
        }

        // Enforce monthly win limit before allowing a new bid
        if ($this->winsThisMonth($userId) >= $this->allowedWinsThisMonth($userId)) {
            return redirect()->back()->with('error', 'You have reached your monthly featured limit and cannot place further bids this month.');
        }

        $today    = date('Y-m-d');
        $bidModel = new BidModel();
        $existing = $bidModel
            ->where('user_id', $userId)
            ->where('bid_date', $today)
            ->first();

        $db   = Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

        if ($existing) {
            // Update existing bid – increase only
            if ($amount <= (float) $existing['bid_amount']) {
                return redirect()->back()->with('error', 'Your updated bid must be higher than your current bid of £' . number_format((float) $existing['bid_amount'], 2) . '.');
            }

            $bidModel->update($existing['id'], [
                'bid_amount' => $amount,
                'status'     => 'pending',
                'is_active'  => 1,
            ]);

            // Notify alumnus of updated bid status
            $this->sendBidEmail(
                $user['email'],
                $user['name'],
                'Bid Updated',
                'Your bid for <strong>' . date('d M Y', strtotime($today)) . '</strong> has been updated to <strong>£' . number_format($amount, 2) . '</strong>.',
                $this->isCurrentlyWinning($userId, $today, $bidModel)
            );

            return redirect()->to(base_url('bids'))->with('success', 'Bid updated to £' . number_format($amount, 2) . '.');
        }

        // Place new bid
        $bidModel->insert([
            'user_id'    => $userId,
            'bid_date'   => $today,
            'bid_amount' => $amount,
            'status'     => 'pending',
            'is_active'  => 1,
        ]);

        // Notify alumnus of new bid
        $this->sendBidEmail(
            $user['email'],
            $user['name'],
            'Bid Placed',
            'Your bid of <strong>£' . number_format($amount, 2) . '</strong> for <strong>' . date('d M Y', strtotime($today)) . '</strong> has been placed.',
            $this->isCurrentlyWinning($userId, $today, $bidModel)
        );

        return redirect()->to(base_url('bids'))->with('success', 'Bid of £' . number_format($amount, 2) . ' placed successfully.');
    }

    public function delete($id)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $bidModel = new BidModel();
        $bid      = $bidModel->find((int) $id);

        if ($bid && (int) $bid['user_id'] === (int) session()->get('user_id') && $bid['status'] === 'pending') {
            $bidModel->delete((int) $id);
        }

        return redirect()->to(base_url('bids'))->with('success', 'Bid cancelled.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Number of times this user has been featured as Alumni of the Day this calendar month.
     */
    private function winsThisMonth(int $userId): int
    {
        return (new FeaturedWinnerModel())
            ->where('user_id', $userId)
            ->where('feature_date >=', date('Y-m-01'))
            ->where('feature_date <=', date('Y-m-t'))
            ->countAllResults();
    }

    /**
     * Maximum wins allowed this calendar month.
     * Base is 3; attending a university alumni event grants +1.
     */
    private function allowedWinsThisMonth(int $userId): int
    {
        $db    = \Config\Database::connect();
        $extra = 0;

        if ($db->tableExists('alumni_event_participation')) {
            $extra = $db->table('alumni_event_participation')
                ->where('user_id', $userId)
                ->where('event_date >=', date('Y-m-01'))
                ->where('event_date <=', date('Y-m-t'))
                ->countAllResults() > 0 ? 1 : 0;
        }

        return 3 + $extra;
    }

    /**
     * Returns true if this user currently holds the highest bid for the given date.
     */
    private function isCurrentlyWinning(int $userId, string $date, BidModel $bidModel): bool
    {
        $top = $bidModel
            ->where('bid_date', $date)
            ->where('is_active', 1)
            ->orderBy('bid_amount', 'DESC')
            ->first();

        return $top && (int) $top['user_id'] === $userId;
    }

    /**
     * Send a bid notification email to the alumnus.
     * NOTE: bid amount of competitors is never included – blind bidding is preserved.
     */
    private function sendBidEmail(string $toEmail, string $toName, string $subject, string $bodyLine, bool $isWinning): void
    {
        $statusLine = $isWinning
            ? '<p style="color:green;"><strong>Status: You are currently the highest bidder.</strong></p>'
            : '<p style="color:orange;"><strong>Status: You are currently losing. You may increase your bid.</strong></p>';

        $body = '<p>Hi ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
              . '<p>' . $bodyLine . '</p>'
              . $statusLine
              . '<p>Log in to <a href="' . base_url('bids') . '">manage your bids</a>.</p>';

        $emailService = \Config\Services::email();
        $emailService->initialize([
            'protocol'   => env('EMAIL_PROTOCOL', 'smtp'),
            'SMTPHost'   => env('EMAIL_SMTP_HOST', ''),
            'SMTPUser'   => env('EMAIL_SMTP_USER', ''),
            'SMTPPass'   => env('EMAIL_SMTP_PASS', ''),
            'SMTPPort'   => (int) env('EMAIL_SMTP_PORT', 587),
            'SMTPCrypto' => env('EMAIL_SMTP_CRYPTO', 'tls'),
            'mailType'   => 'html',
            'charset'    => 'UTF-8',
        ]);
        $emailService->setFrom(env('EMAIL_FROM_ADDRESS', 'no-reply@iit.ac.lk'), env('EMAIL_FROM_NAME', 'Alumni Influencer'));
        $emailService->setTo($toEmail);
        $emailService->setSubject('Alumni Influencer – ' . $subject);
        $emailService->setMessage($body);
        $emailService->send(false);
    }
}
