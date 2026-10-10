<?php

require_once __DIR__ . '/LalaNormalizer.php';

class LalaIntentDetector
{
    private $normalizer;

    private $greetingPatterns = [
        'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening',
        'greetings', 'howdy', 'yo', 'what\'s up',
    ];

    private $closingPatterns = [
        'bye', 'goodbye', 'see you', 'ok thanks', 'okay thanks', 'that\'s all', 'that is all',
    ];

    private $helpPatterns = [
        'help', 'what can you do', 'how does this work', 'what are you',
        'who are you', 'what is this', 'assist me',
    ];

    private $followUpPatterns = [
        'when is it effective', 'what version', 'is it published', 'effective date',
        'when did it take effect', 'when was it published', 'what is the status',
        'is it current', 'is it active', 'what about', 'tell me more',
        'more details', 'full policy', 'show details', 'view details',
        'can you show', 'i want to see', 'attachment', 'download',
        'acknowledgement', 'acknowledgment', 'deadline',
        'when does it start', 'when does it expire', 'when is the deadline',
        'who approved it', 'what is the policy code', 'show the policy',
        'full content', 'read the policy', 'policy details',
        'give me the details', 'more information', 'elaborate',
        'explain more', 'expand on that', 'go deeper',
    ];

    private $policyIntentPatterns = [
        'remote work' => 'REMOTE_WORK',
        'work from home' => 'REMOTE_WORK',
        'wfh' => 'REMOTE_WORK',
        'working remotely' => 'REMOTE_WORK',
        'remote employee' => 'REMOTE_WORK',
        'home based work' => 'REMOTE_WORK',
        'telecommute' => 'REMOTE_WORK',
        'telework' => 'REMOTE_WORK',
        'flexible work arrangement' => 'REMOTE_WORK',
        'remote work policy' => 'REMOTE_WORK',
        'work remotely' => 'REMOTE_WORK',
        'teleworking' => 'REMOTE_WORK',
        'distributed team' => 'REMOTE_WORK',
        'home based' => 'REMOTE_WORK',
        'remote working' => 'REMOTE_WORK',
        'code of conduct' => 'CODE_OF_CONDUCT',
        'conduct policy' => 'CODE_OF_CONDUCT',
        'employee conduct' => 'CODE_OF_CONDUCT',
        'professional conduct' => 'CODE_OF_CONDUCT',
        'workplace behavior' => 'CODE_OF_CONDUCT',
        'ethical guidelines' => 'CODE_OF_CONDUCT',
        'employee behavior' => 'CODE_OF_CONDUCT',
        'standards of behavior' => 'CODE_OF_CONDUCT',
        'ethical standards' => 'CODE_OF_CONDUCT',
        'workplace conduct' => 'CODE_OF_CONDUCT',
        'professional ethics' => 'CODE_OF_CONDUCT',
        'employee ethics' => 'CODE_OF_CONDUCT',
        'conduct rules' => 'CODE_OF_CONDUCT',
        'behavior policy' => 'CODE_OF_CONDUCT',
        'data privacy' => 'DATA_PRIVACY',
        'privacy policy' => 'DATA_PRIVACY',
        'personal information' => 'DATA_PRIVACY',
        'personal data' => 'DATA_PRIVACY',
        'data protection' => 'DATA_PRIVACY',
        'privacy rules' => 'DATA_PRIVACY',
        'handling personal information' => 'DATA_PRIVACY',
        'information privacy' => 'DATA_PRIVACY',
        'data security' => 'DATA_PRIVACY',
        'confidentiality' => 'DATA_PRIVACY',
        'data breach' => 'DATA_PRIVACY',
        'privacy compliance' => 'DATA_PRIVACY',
        'personal data protection' => 'DATA_PRIVACY',
        'data governance' => 'DATA_PRIVACY',
        'data handling' => 'DATA_PRIVACY',
        'sensitive personal information' => 'DATA_PRIVACY',
        'it security' => 'IT_SECURITY',
        'information security' => 'IT_SECURITY',
        'computer security' => 'IT_SECURITY',
        'system security' => 'IT_SECURITY',
        'security requirements' => 'IT_SECURITY',
        'access control' => 'IT_SECURITY',
        'cybersecurity' => 'IT_SECURITY',
        'network security' => 'IT_SECURITY',
        'password policy' => 'IT_SECURITY',
        'security protocols' => 'IT_SECURITY',
        'information protection' => 'IT_SECURITY',
        'system access' => 'IT_SECURITY',
        'digital security' => 'IT_SECURITY',
        'employee handbook' => 'EMPLOYEE_HANDBOOK',
        'handbook' => 'EMPLOYEE_HANDBOOK',
        'hr handbook' => 'EMPLOYEE_HANDBOOK',
        'employee guide' => 'EMPLOYEE_HANDBOOK',
        'company handbook' => 'EMPLOYEE_HANDBOOK',
        'employee policies' => 'EMPLOYEE_HANDBOOK',
        'benefits and procedures' => 'EMPLOYEE_HANDBOOK',
        'company policies' => 'EMPLOYEE_HANDBOOK',
        'employee manual' => 'EMPLOYEE_HANDBOOK',
        'hr manual' => 'EMPLOYEE_HANDBOOK',
        'staff handbook' => 'EMPLOYEE_HANDBOOK',
        'company guide' => 'EMPLOYEE_HANDBOOK',
        'policy handbook' => 'EMPLOYEE_HANDBOOK',
        'employee resource' => 'EMPLOYEE_HANDBOOK',
        'new hire guide' => 'EMPLOYEE_HANDBOOK',
        'harassment' => 'ANTI_HARASSMENT',
        'anti harassment' => 'ANTI_HARASSMENT',
        'workplace harassment' => 'ANTI_HARASSMENT',
        'discrimination' => 'ANTI_HARASSMENT',
        'harassment policy' => 'ANTI_HARASSMENT',
        'workplace discrimination' => 'ANTI_HARASSMENT',
        'sexual harassment' => 'ANTI_HARASSMENT',
        'gender harassment' => 'ANTI_HARASSMENT',
        'zero tolerance' => 'ANTI_HARASSMENT',
        'harassment prevention' => 'ANTI_HARASSMENT',
        'conduct policy' => 'ANTI_HARASSMENT',
        'respectful workplace' => 'ANTI_HARASSMENT',
        'anti harassment policy' => 'ANTI_HARASSMENT',
        'discrimination policy' => 'ANTI_HARASSMENT',
        'social media' => 'SOCIAL_MEDIA',
        'facebook' => 'SOCIAL_MEDIA',
        'instagram' => 'SOCIAL_MEDIA',
        'tiktok' => 'SOCIAL_MEDIA',
        'linkedin' => 'SOCIAL_MEDIA',
        'online posts' => 'SOCIAL_MEDIA',
        'social media rules' => 'SOCIAL_MEDIA',
        'employee social media' => 'SOCIAL_MEDIA',
        'social media policy' => 'SOCIAL_MEDIA',
        'social networking' => 'SOCIAL_MEDIA',
        'digital conduct' => 'SOCIAL_MEDIA',
        'social platforms' => 'SOCIAL_MEDIA',
        'company social media' => 'SOCIAL_MEDIA',
        'online behavior' => 'SOCIAL_MEDIA',
        'business continuity' => 'BUSINESS_CONTINUITY',
        'emergency procedures' => 'BUSINESS_CONTINUITY',
        'emergency operations' => 'BUSINESS_CONTINUITY',
        'disaster recovery' => 'BUSINESS_CONTINUITY',
        'disruptions' => 'BUSINESS_CONTINUITY',
        'continuity plan' => 'BUSINESS_CONTINUITY',
        'emergency response' => 'BUSINESS_CONTINUITY',
        'business continuity plan' => 'BUSINESS_CONTINUITY',
        'crisis management' => 'BUSINESS_CONTINUITY',
        'disaster recovery plan' => 'BUSINESS_CONTINUITY',
        'contingency plan' => 'BUSINESS_CONTINUITY',
        'emergency plan' => 'BUSINESS_CONTINUITY',
        'resilience plan' => 'BUSINESS_CONTINUITY',
        'operational continuity' => 'BUSINESS_CONTINUITY',
        'workplace safety' => 'WORKPLACE_SAFETY',
        'safety policy' => 'WORKPLACE_SAFETY',
        'health and safety' => 'WORKPLACE_SAFETY',
        'safety procedures' => 'WORKPLACE_SAFETY',
        'workplace hazards' => 'WORKPLACE_SAFETY',
        'employee safety' => 'WORKPLACE_SAFETY',
        'safe workplace' => 'WORKPLACE_SAFETY',
        'occupational safety' => 'WORKPLACE_SAFETY',
        'safety standards' => 'WORKPLACE_SAFETY',
        'ppe' => 'WORKPLACE_SAFETY',
        'personal protective equipment' => 'WORKPLACE_SAFETY',
        'hazard identification' => 'WORKPLACE_SAFETY',
        'safety compliance' => 'WORKPLACE_SAFETY',
        'workplace health' => 'WORKPLACE_SAFETY',
        'incident prevention' => 'WORKPLACE_SAFETY',
        'safety training' => 'WORKPLACE_SAFETY',
        'expense reimbursement' => 'EXPENSE_REIMBURSEMENT',
        'reimbursement' => 'EXPENSE_REIMBURSEMENT',
        'expense report' => 'EXPENSE_REIMBURSEMENT',
        'expense claim' => 'EXPENSE_REIMBURSEMENT',
        'employee expenses' => 'EXPENSE_REIMBURSEMENT',
        'reimbursable expenses' => 'EXPENSE_REIMBURSEMENT',
        'reimbursement process' => 'EXPENSE_REIMBURSEMENT',
        'expense policy' => 'EXPENSE_REIMBURSEMENT',
        'travel expense' => 'EXPENSE_REIMBURSEMENT',
        'business expense' => 'EXPENSE_REIMBURSEMENT',
        'claim reimbursement' => 'EXPENSE_REIMBURSEMENT',
        'expense submission' => 'EXPENSE_REIMBURSEMENT',
        'reimbursable' => 'EXPENSE_REIMBURSEMENT',
        'expense allowance' => 'EXPENSE_REIMBURSEMENT',
    ];

    public function __construct()
    {
        $this->normalizer = new LalaNormalizer();
    }

    public function detect(string $rawInput, array $context): array
    {
        $normalized = $this->normalizer->normalize($rawInput);
        $lower = strtolower($normalized);

        if ($lower === '') {
            return ['intent' => 'UNKNOWN', 'confidence' => 'LOW'];
        }

        if ($this->isGreeting($lower)) {
            return ['intent' => 'GREETING', 'confidence' => 'HIGH'];
        }

        if ($this->isClosing($lower)) {
            return ['intent' => 'GOODBYE', 'confidence' => 'HIGH'];
        }

        if ($this->isHelp($lower)) {
            return ['intent' => 'HELP', 'confidence' => 'HIGH'];
        }

        if ($this->isThanks($lower)) {
            return ['intent' => 'THANKS', 'confidence' => 'HIGH'];
        }

        $followUpResult = $this->detectFollowUp($lower, $context);
        if ($followUpResult) {
            return $followUpResult;
        }

        $policyIntent = $this->detectPolicyIntent($lower);
        if ($policyIntent) {
            return ['intent' => $policyIntent, 'confidence' => 'HIGH'];
        }

        if ($this->looksLikePolicySearch($lower)) {
            return ['intent' => 'POLICY_SEARCH', 'confidence' => 'MEDIUM'];
        }

        if ($this->looksLikeLaborLawSearch($lower)) {
            return ['intent' => 'LABOR_LAW_SEARCH', 'confidence' => 'MEDIUM'];
        }

        return ['intent' => 'UNKNOWN', 'confidence' => 'LOW'];
    }

    private function isGreeting(string $lower): bool
    {
        foreach ($this->greetingPatterns as $g) {
            if ($lower === $g || str_starts_with($lower, $g . ' ')) {
                return true;
            }
        }
        return preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))/', $lower) === 1 && str_word_count($lower) <= 3;
    }

    private function isClosing(string $lower): bool
    {
        foreach ($this->closingPatterns as $c) {
            if ($lower === $c || str_contains($lower, $c)) {
                return true;
            }
        }
        return false;
    }

    private function isHelp(string $lower): bool
    {
        foreach ($this->helpPatterns as $h) {
            if ($lower === $h || str_contains($lower, $h)) {
                return true;
            }
        }
        return false;
    }

    private function isThanks(string $lower): bool
    {
        return str_contains($lower, 'thank') || str_contains($lower, 'thanks') || str_contains($lower, 'thx');
    }

    private function detectFollowUp(string $lower, array $context): ?array
    {
        if (empty($context['last_intent']) && empty($context['last_source'])) {
            return null;
        }

        foreach ($this->followUpPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return ['intent' => 'FOLLOW_UP', 'confidence' => 'MEDIUM'];
            }
        }

        return null;
    }

    private function detectPolicyIntent(string $lower): ?string
    {
        foreach ($this->policyIntentPatterns as $phrase => $intent) {
            if (str_contains($lower, $phrase)) {
                return $intent;
            }
        }
        return null;
    }

    private function looksLikePolicySearch(string $lower): bool
    {
        $policyWords = [
            'policy', 'policies', 'handbook', 'acknowledgement', 'acknowledgment',
            'hr policy', 'company policy', 'internal policy', 'remote work policy',
            'safety policy', 'privacy policy', 'security policy', 'conduct policy',
            'expense policy', 'social media policy', 'continuity plan',
            'company handbook', 'employee guide', 'code of conduct',
            'anti harassment policy', 'workplace safety policy', 'data privacy policy',
            'it security policy', 'employee handbook', 'business continuity',
        ];
        foreach ($policyWords as $w) {
            if (str_contains($lower, $w)) {
                return true;
            }
        }
        return false;
    }

    private function looksLikeLaborLawSearch(string $lower): bool
    {
        $lawWords = [
            'labor law', 'labour law', 'law says', 'legal requirement', 'statute',
            'act of', 'republic act', 'presidential decree', 'department order',
            'labor code', 'philippine law', 'dole', 'irr',
            'pd 442', 'pd442', 'ra 6725', 'ra6725', 'ra 7877', 'ra7877',
            'ra 11313', 'ra11313', 'ra 11058', 'ra11058', 'ra 11210', 'ra11210',
            'ra 8187', 'ra8187', 'ra 8972', 'ra8972', 'ra 11861', 'ra11861',
            'ra 9504', 'ra9504', 'ra 10173', 'ra10173', 'ra 11551', 'ra11551',
            'magna carta', 'wage rationalization', 'occupational safety',
            'mental health act', 'data privacy act', 'safe spaces act',
            'paternity leave', 'maternity leave', 'solo parent', 'expanded solo parent',
            'anti sexual harassment', 'anti-discrimination', 'gender equality',
            'retirement pay', 'security of tenure', 'separation pay',
            'minimum wage', 'overtime pay', 'holiday pay', 'night differential',
            '13th month', 'thirteenth month', 'service incentive leave',
            'government contribution', 'sss', 'philhealth', 'pagibig',
            'alien employment', 'foreign national', 'aep',
            'labor relations', 'collective bargaining', 'union',
            'certification election', 'bargaining representative', 'seba',
            'free legal assistance', 'labor attorneys', 'labor justice',
            'labor education', 'workers education', 'labor rights',
            'tripartism', 'tripartite',
        ];
        foreach ($lawWords as $w) {
            if (str_contains($lower, $w)) {
                return true;
            }
        }
        return false;
    }
}
