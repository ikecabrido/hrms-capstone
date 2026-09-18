Table em_departments {
  department_id int [pk]
  department_code varchar(50) [unique]
  department_name varchar(150) [not null]
  description text
  status varchar(20) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

Table em_roles {
  role_id int [pk]
  role_name varchar(100) [not null]
  description text
  status varchar(20) [default: 'active']
  created_at timestamp
}

Table em_positions {
  position_id int [pk]
  position_name varchar(150) [not null]
  slot_count int [default: 0]
  department_id int
  status varchar(20) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

Table em_employees {
  employee_id int [pk]
  employee_code varchar(50) [unique, not null]
  first_name varchar(100) [not null]
  middle_name varchar(100)
  last_name varchar(100) [not null]
  suffix varchar(20)
  gender varchar(20)
  birth_date date
  email varchar(150)
  mobile_no varchar(50)
  phone_no varchar(50)
  current_address text
  permanent_address text
  department_id int
  position_id int
  hire_date date
  employment_status varchar(30) [default: 'ACTIVE']
  employment_type varchar(30)
  created_at timestamp
  updated_at timestamp
}

Table user_account {
  user_id int [pk]
  employee_id int [unique]
  role_id int
  password varchar(255) [not null]
  theme varchar(50) [default: 'default']
  profile_pic varchar(255)
  account_status varchar(30) [default: 'Active']
  last_login timestamp
  password_changed_at timestamp
  failed_login_attempts int [default: 0]
  locked_until timestamp
  created_at timestamp
  updated_at timestamp
}

Table eer_announcements {
  eer_announcements_id int [pk]
  title varchar(255)
  content text
  created_by_employee_id int
  created_at datetime
  target_audience varchar(100) [default: 'all']
  type varchar(30) [default: 'announcement']
  department varchar(100)
  priority varchar(50) [default: 'normal']
  category varchar(100) [default: 'general']
}

Table eer_notifications {
  id int [pk, increment]
  employee_id int [not null]
  message text
  type varchar(50)
  is_read bool [default: false]
  created_at datetime
}

Table eer_messages {
  eer_message_id int [pk]
  sender_id int
  receiver_id int
  message text
  timestamp datetime
}

Table eer_social_posts {
  eer_social_post_id int [pk, increment]
  employee_id int
  user_id int
  author_type varchar(20) [not null]
  item_type varchar(20) [default: 'post']
  content text
  file_name varchar(255)
  file_path varchar(500)
  file_size int
  file_type varchar(10)
  description text
  created_at datetime
}

Table eer_reactions {
  eer_reaction_id int [pk]
  post_id int
  target_type varchar(20) [default: 'post']
  target_id int
  employee_id int
  user_id int
  user_type varchar(20) [default: 'employee']
  type varchar(20) [default: 'like']
  created_at datetime
}

Table eer_comments {
  eer_comment_id int [pk]
  post_id int
  employee_id int
  comment text
  created_at datetime
  user_id int
  user_type varchar(20) [default: 'employee']
}

Table eer_replies {
  eer_reply_id int [pk]
  comment_id int
  post_id int
  parent_reply_id int
  employee_id int
  user_id int
  user_type varchar(20) [default: 'employee']
  content text
  mentioned_user_id int
  created_at datetime
}

Table eer_groups {
  eer_group_id int [pk]
  name varchar(255)
  created_by_employee_id int
  created_at datetime
}

Table eer_group_members {
  eer_group_member_id int [pk]
  group_id int
  employee_id int
}

Table eer_forums {
  eer_forum_id int [pk, increment]
  title varchar(255)
  description text
  category varchar(100)
  created_by_employee_id int
  created_at datetime
}

Table eer_projects {
  eer_project_id int [pk, increment]
  name varchar(255)
  description text
  deadline datetime
  status varchar(50) [default: 'planning']
  created_by_employee_id int
  created_at datetime
  updated_at datetime
}

Table eer_surveys {
  eer_survey_id int [pk, increment]
  title varchar(255)
  is_anonymous bool [default: false]
  survey_type varchar(100) [default: 'engagement']
  created_by_employee_id int
  description text
  created_at datetime
  feedback_id int
}

Table eer_survey_questions {
  eer_survey_question_id int [pk, increment]
  survey_id int
  question_text text
  type varchar(50)
}

Table eer_survey_targets {
  eer_survey_target_id int [pk]
  survey_id int
  employee_id int
  status varchar(20) [default: 'pending']
}

Table eer_survey_responses {
  eer_survey_response_id int [pk]
  survey_id int
  employee_id int
  answers json
  target_employee_id int
}

Table eer_survey_answers {
  eer_survey_answer_id int [pk]
  response_id int
  question_id int
  answer text
}

Table eer_survey_feedback_id {
  eer_survey_feedback_id_id int [pk, increment]
  survey_id int
  employee_id varchar(50)
  comment text
  rating int
  evaluator_type varchar(50) [default: 'Self']
  category varchar(100) [default: 'general']
  is_anonymous bool [default: false]
  evaluation_date datetime
}

Table eer_recognitions {
  eer_recognition_id int [pk, increment]
  sender_id int [not null]
  receiver_id int [not null]
  message text
  points int
  created_at datetime
  category varchar(100) [default: 'general']
  source varchar(30) [default: 'manual']
  performance_report_id int
  status varchar(20) [default: 'approved']
  leaderboard_position int
  department varchar(100)
  updated_at timestamp
}

Table eer_award_history {
  eer_award_history_id int [pk, increment]
  employee_id int
  award_name varchar(255)
  created_at timestamp
  reason text
  nominated_by int
  updated_at timestamp
  award_type varchar(40) [default: 'special_recognition']
  points int [default: 0]
  status varchar(20) [default: 'nominated']
  vote_count int [default: 0]
  performance_score decimal(5,2)
  month_year varchar(7)
  award_icon varchar(255)
}

Table eer_award_votes {
  eer_award_vote_id int [pk]
  award_history_id int [not null]
  voter_user_id int [not null]
  nominee_employee_id int
  created_at datetime
}

Table eer_badges {
  eer_badge_id int [pk]
  name varchar(100)
  description text
  icon varchar(255)
  tier varchar(20) [default: 'bronze']
  points_value int [default: 10]
  category varchar(100) [default: 'achievement']
  requirement_type varchar(100) [default: 'manual']
  requirement_value int
  status varchar(20) [default: 'active']
  created_at datetime
  updated_at timestamp
}

Table eer_employee_badges {
  eer_employee_badge_id int [pk]
  employee_id int
  badge_id int
  awarded_at datetime
  awarded_by int
  reason text
  performance_linked bool [default: false]
  performance_score decimal(5,2)
  updated_at timestamp
}

Table eer_rewards {
  eer_reward_id int [pk]
  name varchar(255)
  description varchar(255)
  points_required int
  category varchar(100) [default: 'general']
  icon varchar(255)
  tier varchar(20) [default: 'bronze']
  performance_requirement decimal(5,2)
  status varchar(20) [default: 'active']
  created_at datetime
  updated_at timestamp
}

Table eer_reward_redemptions {
  eer_reward_redemption_id int [pk]
  employee_id int
  reward_id int
  points_used int
  redeemed_at datetime
  status varchar(20) [default: 'pending']
  approved_by int
  approved_at datetime
  rejection_reason text
  notes text
  updated_at timestamp
}

Table eer_grievances {
  eer_grievance_id int [pk]
  employee_id int
  subject varchar(255)
  description text
  status varchar(20) [default: 'pending']
  resolution_of_complaint text
  created_at datetime
  priority varchar(20) [default: 'medium']
  category varchar(100)
  anonymous bool [default: false]
  attachment_path varchar(255)
  confidential bool [default: false]
  action_taken text
  satisfaction_rating int
  satisfaction_comment text
  resolved_at datetime
  escalation_level varchar(50)
  escalation_reason text
  updated_at timestamp
  created_by_employee_id int
  payslip_id int
  gross_pay decimal(10,2)
  total_deductions decimal(10,2)
  net_pay decimal(10,2)
  payslip_information text
}

Table eer_grievance_updates {
  id int [pk, increment]
  grievance_id int
  update_text text
  updated_by_employee_id int
  updated_at datetime
}

Table eer_grievance_attendance_links {
  id int [pk]
  grievance_id int [not null]
  employee_id int [not null]
  attendance_id int
  attendance_date date
  attendance_status varchar(50)
  late_minutes int [default: 0]
  early_out_minutes int [default: 0]
  linked_at datetime
}

Table eer_grievance_payroll {
  grievance_id int [pk]
  employee_id int [not null]
  payroll_module varchar(100) [not null]
  reference_id int
  complaint_title varchar(150) [not null]
  complaint_details text [not null]
  attachment varchar(255)
  status varchar(20) [default: 'Pending']
  created_at timestamp
  resolved_at timestamp
}

Table eer_employee_activities {
  activity_id int [pk, increment]
  employee_id int
  activity_type varchar(50)
  activity_description text
  related_id int
  created_at datetime
}

Ref: em_positions.department_id > em_departments.department_id
Ref: em_employees.department_id > em_departments.department_id
Ref: em_employees.position_id > em_positions.position_id
Ref: user_account.employee_id - em_employees.employee_id
Ref: user_account.role_id > em_roles.role_id
Ref: eer_announcements.created_by_employee_id > em_employees.employee_id
Ref: eer_notifications.employee_id > em_employees.employee_id
Ref: eer_messages.sender_id > em_employees.employee_id
Ref: eer_messages.receiver_id > em_employees.employee_id
Ref: eer_social_posts.employee_id > em_employees.employee_id
Ref: eer_social_posts.user_id > user_account.user_id
Ref: eer_reactions.employee_id > em_employees.employee_id
Ref: eer_reactions.user_id > user_account.user_id
Ref: eer_comments.employee_id > em_employees.employee_id
Ref: eer_comments.user_id > user_account.user_id
Ref: eer_replies.employee_id > em_employees.employee_id
Ref: eer_replies.user_id > user_account.user_id
Ref: eer_groups.created_by_employee_id > em_employees.employee_id
Ref: eer_group_members.group_id > eer_groups.eer_group_id
Ref: eer_group_members.employee_id > em_employees.employee_id
Ref: eer_forums.created_by_employee_id > em_employees.employee_id
Ref: eer_projects.created_by_employee_id > em_employees.employee_id
Ref: eer_surveys.created_by_employee_id > em_employees.employee_id
Ref: eer_survey_questions.survey_id > eer_surveys.eer_survey_id
Ref: eer_survey_targets.survey_id > eer_surveys.eer_survey_id
Ref: eer_survey_targets.employee_id > em_employees.employee_id
Ref: eer_survey_responses.survey_id > eer_surveys.eer_survey_id
Ref: eer_survey_responses.employee_id > em_employees.employee_id
Ref: eer_survey_answers.response_id > eer_survey_responses.eer_survey_response_id
Ref: eer_survey_answers.question_id > eer_survey_questions.eer_survey_question_id
Ref: eer_survey_feedback_id.survey_id > eer_surveys.eer_survey_id
Ref: eer_recognitions.sender_id > em_employees.employee_id
Ref: eer_recognitions.receiver_id > em_employees.employee_id
Ref: eer_award_history.employee_id > em_employees.employee_id
Ref: eer_award_history.nominated_by > em_employees.employee_id
Ref: eer_award_votes.award_history_id > eer_award_history.eer_award_history_id
Ref: eer_award_votes.voter_user_id > user_account.user_id
Ref: eer_award_votes.nominee_employee_id > em_employees.employee_id
Ref: eer_employee_badges.employee_id > em_employees.employee_id
Ref: eer_employee_badges.badge_id > eer_badges.eer_badge_id
Ref: eer_employee_badges.awarded_by > em_employees.employee_id
Ref: eer_reward_redemptions.employee_id > em_employees.employee_id
Ref: eer_reward_redemptions.reward_id > eer_rewards.eer_reward_id
Ref: eer_reward_redemptions.approved_by > em_employees.employee_id
Ref: eer_grievances.employee_id > em_employees.employee_id
Ref: eer_grievances.created_by_employee_id > em_employees.employee_id
Ref: eer_grievance_updates.grievance_id > eer_grievances.eer_grievance_id
Ref: eer_grievance_updates.updated_by_employee_id > em_employees.employee_id
Ref: eer_grievance_attendance_links.grievance_id > eer_grievances.eer_grievance_id
Ref: eer_grievance_attendance_links.employee_id > em_employees.employee_id
Ref: eer_grievance_payroll.employee_id > em_employees.employee_id
Ref: eer_employee_activities.employee_id > em_employees.employee_id
