<?php

namespace App\Services;

use App\Models\Contact;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactCsvExporter
{
    private const HEADER = ['ID', '氏名', '性別', 'メールアドレス', '電話番号', '住所', '建物名', 'お問い合わせの種類', 'お問い合わせ内容', '作成日時'];

    private const BOM = "\xEF\xBB\xBF";

    public function download(array $filters): StreamedResponse
    {
        $contacts = Contact::with('category')->filter($filters)->newest();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, self::BOM);
            fputcsv($handle, self::HEADER);

            foreach ($contacts->lazy() as $contact) {
                fputcsv($handle, $this->toRow($contact));
            }

            fclose($handle);
        }, 'contacts_'.now()->format('YmdHis').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function toRow(Contact $contact): array
    {
        return [
            $contact->id,
            $contact->full_name,
            $contact->gender_label,
            $contact->email,
            $contact->tel,
            $contact->address,
            $contact->building ?? '',
            $contact->category?->content ?? '',
            $contact->detail,
            $contact->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
