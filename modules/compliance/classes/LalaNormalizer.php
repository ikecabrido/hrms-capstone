<?php

class LalaNormalizer
{
    private $typoMap = [
        'privacey' => 'privacy',
        'harasment' => 'harassment',
        'reimbursment' => 'reimbursement',
        'saftey' => 'safety',
        'workplace saftey' => 'workplace safety',
        'employye' => 'employee',
        'employeer' => 'employer',
        'employess' => 'employees',
        'benifit' => 'benefit',
        'benifits' => 'benefits',
        'bennefit' => 'benefit',
        'maternaty' => 'maternity',
        'materniy' => 'maternity',
        'paternaty' => 'paternity',
        'contibution' => 'contribution',
        'contibutions' => 'contributions',
        'philheath' => 'philhealth',
        'philhealt' => 'philhealth',
        'holidy' => 'holiday',
        'holidayd' => 'holiday',
        'nightdif' => 'night differential',
        'night dif' => 'night differential',
        'nightdifferential' => 'night differential',
        'pag ibig' => 'pag-ibig',
        'pagibigcontrib' => 'pag-ibig contribution',
        'pag-ibigcontrib' => 'pag-ibig contribution',
        'res day' => 'rest day',
        'restday' => 'rest day',
        'rest days' => 'rest day',
        'sundae' => 'sunday',
        'sundey' => 'sunday',
        'sunduy' => 'sunday',
        'workd' => 'worked',
        'workded' => 'worked',
        'wagem' => 'wage',
        'wagee' => 'wage',
        'overtyme' => 'overtime',
        'overtim' => 'overtime',
        'overtym' => 'overtime',
        'overtimee' => 'overtime',
        'payy' => 'pay',
        'minumum' => 'minimum',
        'remtoe' => 'remote',
        'remotly' => 'remotely',
        'telework' => 'telecommute',
        'telecommute' => 'work from home',
        'cybrsecurity' => 'cybersecurity',
        'cyber security' => 'information security',
        'harrassment' => 'harassment',
        'discriminaton' => 'discrimination',
        'discrimnation' => 'discrimination',
        'continuty' => 'continuity',
        'bussiness' => 'business',
        'expence' => 'expense',
        'expens' => 'expense',
        'reimbursment' => 'reimbursement',
        'reimbursal' => 'reimbursement',
        'handbok' => 'handbook',
        'hand book' => 'handbook',
        'policty' => 'policy',
        'policie' => 'policy',
        'policys' => 'policies',
        'employe' => 'employee',
        'employes' => 'employees',
        'employer' => 'employer',
        'employerr' => 'employer',
        'goverment' => 'government',
        'govenment' => 'government',
        'labour' => 'labor',
        'laber' => 'labor',
    ];

    private $abbreviationMap = [
        'wfh' => 'work from home',
        'WFH' => 'work from home',
        'IT' => 'information technology',
        'HR' => 'human resources',
        'PPE' => 'personal protective equipment',
        'DOLE' => 'department of labor and employment',
        'SSS' => 'social security system',
        'RA' => 'republic act',
        'PD' => 'presidential decree',
        'DO' => 'department order',
        'IRR' => 'implementing rules and regulations',
        'MORP' => 'manual of regulations for private schools',
        'CODI' => 'committee on decorum and investigation',
        'NLRC' => 'national labor relations commission',
        'BIR' => 'bureau of internal revenue',
        'NPC' => 'national privacy commission',
        'PCW' => 'philippine commission on women',
        'DOH' => 'department of health',
        'TESDA' => 'technical education and skills development authority',
        'CHED' => 'commission on higher education',
        'AEP' => 'alien employment permit',
        'SEBA' => 'sole and exclusive bargaining agent',
        'TAV' => 'technical and advisory visit',
        'OSHS' => 'occupational safety and health standards',
        'OSH' => 'occupational safety and health',
        'VAWC' => 'violence against women and children',
        'NSTP' => 'national service training program',
        'GAD' => 'gender and development',
        'SPIC' => 'solo parent identification card',
        'UTP' => 'understudy training program',
        'LAO' => 'labor attorneys office',
        'LRD' => 'legal representation division',
        'SEnA' => 'single entry approach',
        'CLEP' => 'clinical legal education program',
        'NWPC' => 'national wages and productivity commission',
        'RTWPB' => 'regional tripartite wages and productivity board',
        'MWE' => 'minimum wage earner',
        'HEI' => 'higher education institution',
        'HEIs' => 'higher education institutions',
        'TVI' => 'technical vocational institution',
        'TVIs' => 'technical vocational institutions',
        'PIC' => 'personal information controller',
        'PIP' => 'personal information processor',
    ];

    public function normalize(string $input): string
    {
        $q = $input;
        $q = str_replace(['&nbsp;', '  '], ' ', $q);
        $q = preg_replace('/\s+/', ' ', $q);
        $q = trim($q);

        $lower = strtolower($q);
        foreach ($this->typoMap as $bad => $good) {
            $lower = str_replace($bad, $good, $lower);
        }

        foreach ($this->abbreviationMap as $abbr => $full) {
            $abbrLower = strtolower($abbr);
            $fullLower = strtolower($full);
            if ($abbrLower !== $fullLower) {
                $lower = preg_replace('/\b' . preg_quote($abbrLower, '/') . '\b/', $fullLower, $lower);
            }
        }

        return $lower;
    }

    public function tokenize(string $input): array
    {
        $normalized = $this->normalize($input);
        return preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
