-- Default seeds (production-safe). NO demo content.
SET NAMES utf8mb4;

INSERT IGNORE INTO membership_plans (code, name, description, ads_level, sort_order) VALUES
('free', 'Free User', 'Registered user with full ads and basic member features.', 'full_ads', 10),
('premium', 'Premium User', 'Reduced ads and enhanced access.', 'limited_ads', 20),
('vip', 'VIP User', 'No ads and highest access level.', 'no_ads', 30);

INSERT IGNORE INTO ad_placements (placement_code, name, page_scope, placement_area, device) VALUES
('home_top', 'Home Top Banner', 'home', 'top', 'all'),
('watch_pre_player', 'Watch Page Before Player', 'watch', 'before_player', 'all'),
('watch_pause_ad', 'Watch Page Pause Ad', 'watch', 'player_pause', 'all'),
('listing_between_grid', 'Listing Between Grid', 'listing', 'between_grid', 'all');

-- Feature toggles. Modules whose backend is not fully delivered in this build
-- start with is_enabled = 0 to satisfy the "no dead menu" rule.
INSERT IGNORE INTO feature_toggles (feature_key, feature_name, feature_group, is_enabled) VALUES
('like_dislike',                 'Like / Dislike',                  'engagement', 1),
('favorites',                    'Favorites / Save Video',          'engagement', 1),
('playlists',                    'Playlist / Watchlist',            'engagement', 0),
('comments',                     'Comments',                        'engagement', 0),
('performer_follow',             'Follow Performer / Studio',       'engagement', 0),
('rss_feed',                     'RSS Feed',                        'seo',        0),
('registration',                 'User Registration',               'auth',       1),
('newsletter',                   'Newsletter',                      'mail',       0),
('membership_page',              'Membership Page',                 'membership', 1),
('public_performer_studio_page', 'Public Performer / Studio Pages', 'frontend',   1),
('age_gate',                     'Age Gate',                        'compliance', 1),
('search_autocomplete',          'Search Autocomplete',             'search',     0),
('mail_broadcast',               'Mail Broadcast',                  'mail',       0),
('ads_vast_vpaid',               'Ads VAST/VPAID',                  'ads',        0),
('import_csv',                   'CSV Import',                      'video',      0),
('import_json',                  'JSON Import',                     'video',      0);

INSERT IGNORE INTO homepage_sections (section_key, title, section_type, sort_order, status) VALUES
('featured',     'Featured',         'featured',   10, 'active'),
('latest',       'Latest Updates',   'latest',     20, 'active'),
('trending',     'Trending Now',     'trending',   30, 'active'),
('most_viewed',  'Most Viewed',      'most_viewed',40, 'active'),
('popular_tags', 'Popular Tags',     'tag',        50, 'hidden');

-- Default settings
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, value_type, is_public, description) VALUES
('site',     'name',                'StreamHub',                 'string',  1, 'Public site name'),
('site',     'tagline',             'Premium streaming hub',     'string',  1, 'Tagline shown in header/SEO'),
('site',     'description',         'Modern streaming hub.',     'text',    1, 'Default meta description'),
('age_gate', 'enabled',             '1',                         'bool',    0, 'Show age gate to guests'),
('age_gate', 'expiry_days',         '30',                        'int',     0, 'Cookie / record expiry in days'),
('player',   'guest_countdown',     '5',                         'int',     0, 'Guest seconds before play activates'),
('iframe',   'allowlist_extra',     '',                          'text',    0, 'Extra iframe domains, comma-separated'),
('seo',      'default_title_suffix','| StreamHub',               'string',  1, 'Suffix appended to <title>'),
('seo',      'default_robots',      'index_follow',              'string',  0, 'Default robots directive'),
('frontend', 'theme_mode',          'dark',                      'string',  1, 'Frontend theme mode'),
('admin',    'session_idle_minutes','30',                        'int',     0, 'Admin idle session timeout');

-- Cron schedules (rows pre-registered; runner will pick them up)
INSERT IGNORE INTO cron_schedules (job_key, job_name, cron_expression, is_enabled) VALUES
('membership_expiry', 'Membership Expiry Sweep',     '*/15 * * * *', 1),
('queue_drain',       'Drain Pending Queue Jobs',    '* * * * *',    1),
('health_check',      'System Health Check',          '*/30 * * * *', 1);

-- Default access rules
INSERT IGNORE INTO access_rules (rule_key, name, rule_scope, source_type, membership_tier, access_level, action, priority) VALUES
('block_guest_member_features', 'Guests cannot use member features', 'playback', 'any', 'guest', 'registered', 'require_login', 10),
('require_premium', 'Premium content requires premium tier',           'playback', 'any', 'free',  'premium',    'require_membership', 20),
('require_vip',     'VIP content requires VIP tier',                   'playback', 'any', 'free',  'vip',        'require_membership', 30),
('require_vip_from_premium', 'VIP content requires VIP tier (premium)','playback', 'any', 'premium','vip',       'require_membership', 31),
('vip_no_ads',      'VIP sees no ads',                                  'ads',      'any', 'vip',   'any',        'block',              40);

-- Default theme preset (active)
INSERT IGNORE INTO theme_presets (name, is_active, mode, accent_color) VALUES
('Default Dark', 1, 'dark', '#ef4444');

-- Default RSS feed (disabled by default - feature off)
INSERT IGNORE INTO rss_feeds (feed_key, title, feed_type, item_limit, status) VALUES
('latest', 'Latest Updates', 'latest', 50, 'disabled');

-- Default robots rules
INSERT IGNORE INTO robots_rules (user_agent, directive, rule_value, sort_order, status) VALUES
('*', 'allow',    '/',                       1, 'active'),
('*', 'disallow', '/admin',                  2, 'active'),
('*', 'disallow', '/account',                3, 'active'),
('*', 'disallow', '/install',                4, 'active'),
('*', 'sitemap',  '/sitemap.xml',           10, 'active');

-- Default email templates (kept simple; admin can edit later)
INSERT IGNORE INTO email_templates (template_key, language_code, subject, body_html, body_text, status) VALUES
('welcome',                  'default', 'Welcome to {{site_name}}',
 '<p>Hi {{username}},</p><p>Welcome to {{site_name}}.</p>',
 'Hi {{username}}, welcome to {{site_name}}.', 'active'),
('email_verify',             'default', 'Verify your email - {{site_name}}',
 '<p>Hi {{username}},</p><p>Please verify your email by visiting <a href="{{verify_url}}">this link</a>.</p>',
 'Hi {{username}}, verify your email: {{verify_url}}', 'active'),
('password_reset',           'default', 'Reset your password - {{site_name}}',
 '<p>Hi {{username}},</p><p>Reset your password: <a href="{{reset_url}}">{{reset_url}}</a> (valid for 60 minutes).</p>',
 'Hi {{username}}, reset your password: {{reset_url}}', 'active'),
('membership_request_new',   'default', 'Membership request received',
 '<p>Hi {{username}},</p><p>Your membership request has been received. We will contact you shortly.</p>',
 'Your membership request has been received.', 'active'),
('membership_approved',      'default', 'Membership approved',
 '<p>Your membership ({{plan}}) is now active until {{expires_at}}.</p>',
 'Membership approved.', 'active');

-- Default static pages skeletons (admin must edit before publishing)
INSERT IGNORE INTO pages (page_key, title, slug, body, sanitized_body, page_type, status) VALUES
('dmca',          'DMCA Notice',       'dmca',          '<p>Edit this page in the admin panel before publishing.</p>', '<p>Edit this page in the admin panel before publishing.</p>', 'legal',      'draft'),
('privacy',       'Privacy Policy',    'privacy',       '<p>Edit this page in the admin panel before publishing.</p>', '<p>Edit this page in the admin panel before publishing.</p>', 'legal',      'draft'),
('terms',         'Terms of Service',  'terms',         '<p>Edit this page in the admin panel before publishing.</p>', '<p>Edit this page in the admin panel before publishing.</p>', 'legal',      'draft'),
('age-disclaimer','Age Disclaimer',    'age-disclaimer','<p>This site is intended for adults 18 years or older.</p>',   '<p>This site is intended for adults 18 years or older.</p>',   'legal',      'published'),
('faq',           'FAQ',               'faq',           '<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'faq',       'draft'),
('about',         'About',             'about',         '<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'static',    'draft'),
('contact',       'Contact',           'contact',       '<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'contact',   'draft'),
('advertise',     'Advertise',         'advertise',     '<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'advertise', 'draft'),
('webmaster',     'Webmaster / Partner','webmaster',    '<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'webmaster', 'draft'),
('content-disclaimer','Content Disclaimer','content-disclaimer','<p>Edit this page in the admin panel.</p>', '<p>Edit this page in the admin panel.</p>', 'legal', 'draft');

-- Default languages
INSERT IGNORE INTO languages (code, name, is_default, is_active, sort_order) VALUES
('en', 'English', 1, 1, 10);
