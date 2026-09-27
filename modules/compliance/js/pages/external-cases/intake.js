import { qs } from './helpers.js';

export var intakeMappings = {
  'DOLE': {
    category: 'Regulatory Compliance',
    types: ['SEnA / Request for Assistance','Labor Standards Inspection','Unpaid Wages / Salary','Overtime / Holiday / Rest-Day Pay','13th-Month Pay / Benefits','Minimum Wage','Leave / Service Incentive Leave','Occupational Safety and Health','Other Labor Matter']
  },
  'NLRC': {
    category: 'Regulatory Compliance',
    types: ['Illegal Dismissal / Termination','Monetary Claims','Unfair Labor Practice Allegation','Labor Complaint / Case','Execution / Compliance Proceeding','Other NLRC Matter']
  },
  'NCMB': {
    category: 'Regulatory Compliance',
    types: ['Preventive Mediation','Notice of Strike / Lockout','Conciliation / Mediation','Settlement / CBA Matter','Other NCMB Matter']
  },
  'DepEd': {
    category: 'Academic / Education',
    types: ['School Regulatory Compliance','Permit / Recognition','Student Protection','Administrative Complaint','Records / Documentary Compliance','Other DepEd Matter']
  },
  'CHED': {
    category: 'Regulatory Compliance',
    types: ['Program / Institutional Compliance','Permit / Recognition / Authority','Regulatory Inspection / Evaluation','Student-Related Regulatory Complaint','Records / Compliance Submission','Other CHED Matter']
  },
  'POEA/DMW': {
    category: 'Regulatory Compliance',
    types: ['Agency-Specific Compliance','Investigation','Notice / Complaint','Other']
  },
  'Court': {
    category: 'Administrative',
    types: ['Civil','Criminal','Labor-Related','Administrative','Other Court Matter']
  },
  'Other Government Agency': {
    category: 'Regulatory Compliance',
    types: ['Agency-Specific Compliance','Investigation','Notice / Complaint','Other']
  },
  'Other Regulatory Authority': {
    category: 'Regulatory Compliance',
    types: ['Regulatory Inspection','Licensing','Compliance','Enforcement','Other']
  }
};

export var internalComplaintTypes = ['Compensation / Payroll','Leave / Benefits','Working Conditions','Disciplinary Action','Harassment / Discrimination Allegation','Employee Relations / Grievance','Resignation / Termination Dispute','Other / Unclassified'];

export function showEl(el) { if (el) el.style.display = ''; }
export function hideEl(el) { if (el) el.style.display = 'none'; }

export function updateIntakeForm() {
  var source = qs('#ecCreateCaseSource');
  var agency = qs('#ecCreateAgency');
  var specificSelect = qs('#ecCreateSpecificCaseType');
  var caseTypeHidden = qs('#ecCreateCaseType');
  var agencyField = qs('#ecCreateAgencyField');
  var referenceField = qs('#ecReferenceNoField');
  var docketField = qs('#ecDocketField');
  var senaField = qs('#ecSenaField');
  var proceedingField = qs('#ecProceedingStageField');
  var initialStatus = qs('#ecCreateStatus');

  var sourceVal = source ? source.value : '';
  var agencyVal = agency ? agency.value : '';

  if (!sourceVal) {
    hideEl(agencyField);
    hideEl(referenceField);
    hideEl(docketField);
    hideEl(senaField);
    hideEl(proceedingField);
    if (specificSelect) specificSelect.innerHTML = '<option value="">Select source and authority first</option>';
    if (caseTypeHidden) caseTypeHidden.value = '';
    return;
  }

  var agencyRequired = ['Agency Notice','Government Referral'].indexOf(sourceVal) !== -1;
  showEl(agencyField);
  if (agencyRequired) {
    agencyField.querySelector('label').textContent = 'External Authority *';
  } else {
    agencyField.querySelector('label').textContent = 'External Authority';
  }

  hideEl(referenceField);
  hideEl(docketField);
  hideEl(senaField);
  hideEl(proceedingField);

  var options = [];
  var category = '';

  if (['Internal HR Complaint','Employee Directly Reported','Management Referral'].indexOf(sourceVal) !== -1 && !agencyVal) {
    options = internalComplaintTypes.slice();
    category = 'Employee Relations';
  } else if (agencyVal && intakeMappings[agencyVal]) {
    options = intakeMappings[agencyVal].types.slice();
    category = intakeMappings[agencyVal].category;
  }

  if (specificSelect) {
    specificSelect.innerHTML = '<option value="">Select specific case type</option>';
    options.forEach(function (opt) {
      var o = document.createElement('option');
      o.value = opt;
      o.textContent = opt;
      specificSelect.appendChild(o);
    });
  }
  if (caseTypeHidden) caseTypeHidden.value = category;

  if (agencyVal) {
    showEl(referenceField);
    showEl(docketField);
  }

  if (agencyVal === 'DOLE') {
    showEl(senaField);
  }
  if (['NLRC','NCMB','Court'].indexOf(agencyVal) !== -1) {
    showEl(proceedingField);
  }

  if (initialStatus) {
    if (sourceVal === 'Agency Notice' || sourceVal === 'Government Referral') {
      initialStatus.value = 'Notice Received';
    } else if (sourceVal === 'Internal HR Complaint') {
      initialStatus.value = 'Open';
    } else {
      initialStatus.value = 'Draft';
    }
  }
}
