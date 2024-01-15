<?php

namespace Equalify\Controller;

class AllReportsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function run(): void
    {

        $dummyReports = [
            [
                'title' => 'Item 1',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin commodo ipsum et tristique cursus.'
            ],
            [
                'title' => 'Item 2',
                'description' => 'Sed ac risus eget urna laoreet sollicitudin. Nullam elementum erat eu arcu cursus, et ultricies urna gravida.'
            ],
            [
                'title' => 'Item 3',
                'description' => 'Fusce euismod augue nec ante interdum lacinia. Phasellus ultricies turpis a sapien facilisis euismod.'
            ],
            [
                'title' => 'Item 4',
                'description' => 'Vestibulum non tortor ut nisl bibendum eleifend. Duis sit amet orci vel mi efficitur vulputate.'
            ],
            [
                'title' => 'Item 5',
                'description' => 'Aliquam erat volutpat. Integer ut massa et sapien malesuada pellentesque ac vel lectus.'
            ],
            [
                'title' => 'Item 6 this is a test title to see what happens with too many characters on a title block for a card within reports view with over one hundred twenty characters',
                'description' => 'Pellentesque auctor, quam in lacinia lacinia, orci leo tincidunt mi, ut lobortis massa ipsum nec velit. Pellentesque auctor, quam in lacinia lacinia, orci leo tincidunt mi, ut lobortis massa ipsum nec velit Pellentesque auctor, quam in lacinia lacinia, orci leo tincidunt mi, ut lobortis massa ipsum nec velit Pellentesque auctor, quam in lacinia lacinia, orci leo tincidunt mi, ut lobortis massa ipsum nec velit'
            ]
        ];

        $dummyTitle = "title test take one";
        $params = [
            'dummyReports' => $dummyReports,
            'dummyTitle' => $dummyTitle,
        ];
        echo $this->container->get('twig')->render('all-reports.html.twig', $params);
    }
}
