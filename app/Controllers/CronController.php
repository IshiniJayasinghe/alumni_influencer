<?php

namespace App\Controllers;

use App\Models\BidModel;
use App\Models\FeaturedWinnerModel;
use Config\Database;

class CronController extends BaseController
{
    /**
     * Pick today's winning bidder.
     *
     * This endpoint MUST NOT be publicly accessible. It is protected by a
     * shared CRON_SECRET stored in .env. Call it from your server's cron tab:
     *
     *   0 18 * * * curl -s -H "X-Cron-Secret: YOUR_SECRET" \
     *       http://localhost/alumni_influencer_fixed/public/cron/pick-winner
     *
     * The spec requires automated selection each day at 6 PM.
     */
    public function pickWinner()
    {
        // ── Security: reject requests without the correct secret header ────────
        $secret         = env('CRON_SECRET', '');
        $providedSecret = $this->request->getHeaderLine('X-Cron-Secret');

        if ($secret === '' || !hash_equals($secret, $providedSecret)) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $bidModel    = new BidModel();
        $winnerModel = new FeaturedWinnerModel();
        $today       = date('Y-m-d');

        // Idempotent: do nothing if a winner was already selected today
        $existing = $winnerModel->where('feature_date', $today)->first();
        if ($existing) {
            return $this->response->setBody('Winner already selected for today.');
        }

        // Highest bid wins; on tie, the earliest bid (lowest id) wins
        $topBid = $bidModel
            ->where('bid_date', $today)
            ->where('is_active', 1)
            ->orderBy('bid_amount', 'DESC')
            ->orderBy('id', 'ASC')
            ->first();

        if (!$topBid) {
            return $this->response->setBody('No bids found for today — no winner selected.');
        }

        // Record the winner in featured_winners
        $winnerModel->insert([
            'user_id'      => $topBid['user_id'],
            'bid_id'       => $topBid['id'],
            'feature_date' => $today,
            'winning_bid'  => $topBid['bid_amount'],
        ]);

        // Update all bids for today: mark won/lost and deactivate losers
        $allBids = $bidModel->where('bid_date', $today)->findAll();
        foreach ($allBids as $bid) {
            $isWinner = ((int) $bid['id'] === (int) $topBid['id']);
            $bidModel->update($bid['id'], [
                'status'    => $isWinner ? 'won' : 'lost',
                'is_active' => $isWinner ? 1 : 0,
            ]);
        }

        // Notify all bidders of the outcome
        $this->notifyBidders($allBids, $topBid, $today);

        return $this->response->setBody('Winner selected: user_id=' . $topBid['user_id'] . ', winning_bid=£' . $topBid['bid_amount']);
    }

    // -------------------------------------------------------------------------

    private function notifyBidders(array $allBids, array $topBid, string $date): void
    {
        if (empty($allBids)) {
            return;
        }

        $db      = Database::connect();
        $dateStr = date('d M Y', strtotime($date));

        foreach ($allBids as $bid) {
            $user = $db->table('users')->where('id', $bid['user_id'])->get()->getRowArray();
            if (empty($user['email'])) {
                continue;
            }

            $isWinner = ((int) $bid['id'] === (int) $topBid['id']);
            $subject  = $isWinner ? 'You won Alumni of the Day!' : 'Bidding result for ' . $dateStr;

            $body = $isWinner
                ? '<p>Hi ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                  . '<p>Congratulations! Your bid won the <strong>' . $dateStr . '</strong> Alumni of the Day slot. '
                  . 'Your profile will be featured to all students tomorrow.</p>'
                : '<p>Hi ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                  . '<p>Unfortunately your bid for <strong>' . $dateStr . '</strong> was not the highest. '
                  . 'Better luck next time! You can place a bid for tomorrow at <a href="' . base_url('bids') . '">' . base_url('bids') . '</a>.</p>';

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
            $emailService->setTo($user['email']);
            $emailService->setSubject('Alumni Influencer – ' . $subject);
            $emailService->setMessage($body);
            $emailService->send(false);
        }
    }
}
