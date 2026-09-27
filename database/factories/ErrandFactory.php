<?php

namespace Database\Factories;

use App\Models\Errand;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Errand>
 */
class ErrandFactory extends Factory
{
    protected $model = Errand::class;

    public function definition(): array
    {
        $bisayaTasks = [
            [
                'title' => 'Magpahinlo sa Balay',
                'category' => 'cleaning',
                'desc' => 'Kinahanglan og tabang sa pagpanlaba, pagwalis, pagtrapo sa salog, ug paglimpyo sa tibuok balay kay naay bisita ngaabot. Gihangyo ang matinahuron ug kugihan nga worker.'
            ],
            [
                'title' => 'Palit Groceries sa Merkado',
                'category' => 'grocery',
                'desc' => 'Palihog palit sa merkado og mga presko nga utanon, isda, baboy, ug humay. Ilista ang mga kinahanglanon ug ihatid derecho sa balay sa takna nga gikasabutan.'
            ],
            [
                'title' => 'Magpalaba sa Pundok nga Sinina',
                'category' => 'laundry',
                'desc' => 'Naa koy pundok sa mga hugaw nga sinina nga kinahanglan labhan, papag-uga, ug pil-on tarong. Aduna nay sabon ug softener nga gipangandam daan.'
            ],
            [
                'title' => 'Pag-ayo sa Leak sa Gripo',
                'category' => 'repair',
                'desc' => 'Nag-leak ang gripo sa kusina ug kinahanglan kini ayuhon dayon aron dili mausik ang tubig. Palihog pagdala og kaugalingong mga gamit sa pag-ayo.'
            ],
            [
                'title' => 'Pagdala ug Package sa Bus Terminal',
                'category' => 'delivery',
                'desc' => 'Kinahanglan ihatid kining gamay nga package sa bus terminal para padala sa laing lungsod. Palihog siguroha nga maabot kini sa oras sa departure.'
            ],
            [
                'title' => 'Pagbalhin sa mga Muwebles',
                'category' => 'moving',
                'desc' => 'Tabangi ko og alsa ug balhin sa mga bug-at nga kahon, sofa, ug aparador gikan sa sala padulong sa ikaduhang angana sa balay.'
            ],
            [
                'title' => 'Panlaba ug Pamlantsa sa Uniporme',
                'category' => 'laundry',
                'desc' => 'Magpapanlaba ug magpaplantsa sa mga pormal nga uniporme para sa trabaho karong sunod semana. Kinahanglan limpyo kaayo pagkahuman.'
            ],
            [
                'title' => 'Pagputol sa Sagbot sa Tugkaran',
                'category' => 'cleaning',
                'desc' => 'Taas na kaayo ang mga sagbot ug sagbot sa atubangan ug likod sa balay. Kinahanglan i-trim o i-gabas ang mga sagbot aron hapsay tan-awon.'
            ],
            [
                'title' => 'Pagkiskis ug Pagpintal sa Bungbong',
                'category' => 'repair',
                'desc' => 'Gusto nako ipintal ang bungbong sa sala. Kinahanglan una kiskison ang daan nga pintura ug humanon og pintal gamit ang gisupply nako nga gamit.'
            ],
            [
                'title' => 'Paghatid ug Dokumento sa Munisipyo',
                'category' => 'delivery',
                'desc' => 'Naa koy importante nga papeles nga kinahanglan ihatid ug i-file sa opisina sa munisipyo karong buntag. Kinahanglan paspas ug masaligan.'
            ],
            [
                'title' => 'Pagpalit sa Hardwares nga Gamit',
                'category' => 'project',
                'desc' => 'Palihog palit og pipila ka pako, plywood, ug tubo sa hardware store aron mapadayon namo ang gamay nga konstruksyon sa likod sa balay.'
            ],
            [
                'title' => 'Kinahanglan Tabang sa Pag-andam sa Okasyon',
                'category' => 'other',
                'desc' => 'Naa mi gamay nga panagtapok o handurawan sa pamilya. Mangayo ko og tabang sa pag-setup sa mga lingkuranan, lamesa, ug pag-andam sa mga pagkaon.'
            ],
        ];

        $task = $this->faker->randomElement($bisayaTasks);

        $locations = [
            'Brgy. Talisay, Hilongos, Leyte',
            'Brgy. Tejero, Hilongos, Leyte',
            'Brgy. Magnangoy, Hilongos, Leyte',
            'Brgy. Naval, Hilongos, Leyte',
            'Brgy. Santa Cruz, Hilongos, Leyte',
            'Brgy. Owak, Hilongos, Leyte',
            'Brgy. San Juan, Hilongos, Leyte',
            'Brgy. Protection, Hilongos, Leyte',
            'Brgy. Bung-aw, Hilongos, Leyte',
            'Brgy. Lamak, Hilongos, Leyte',
            'Brgy. Liberty, Hilongos, Leyte',
            'Brgy. Lunang, Hilongos, Leyte',
        ];

        return [
            'client_id' => 5,
            'tasker_id' => null,
            'title' => $task['title'],
            'category' => $task['category'],
            'description' => $task['desc'],
            'fee' => $this->faker->randomFloat(2, 150, 2000),
            'deadline' => $this->faker->dateTimeBetween('+1 days', '+14 days'),
            'location' => $this->faker->randomElement($locations),
            'contact' => '09' . $this->faker->numerify('#########'),
            'status' => 'pending',
            'cancel_requested_by_tasker' => false,
            'cancel_requested_by_client' => false,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Errand $errand) {
            Payment::create([
                'errand_id' => $errand->id,
                'client_id' => $errand->client_id,
                'tasker_id' => $errand->tasker_id,
                'amount' => $errand->fee,
                'method' => 'gcash',
                'status' => 'pending',
                'proof_path' => null,
                'proof_submitted_at' => null,
                'paid_at' => null,
                'refund_proof_path' => null,
                'refund_submitted_at' => null,
                'refunded_at' => null,
            ]);
        });
    }
}