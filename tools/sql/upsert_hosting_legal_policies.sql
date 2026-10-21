-- Adds the public policies required by the footer. Review the text with a
-- Malawi-qualified lawyer before deploying it to production.
START TRANSACTION;

INSERT INTO legal_policies (policy_type, title)
VALUES
  ('acceptable_use', 'InstaWebMW Acceptable Use Policy'),
  ('copyright', 'InstaWebMW Copyright and Takedown Policy')
ON DUPLICATE KEY UPDATE title = VALUES(title), updated_at = CURRENT_TIMESTAMP;

INSERT INTO legal_policy_versions (policy_id, version_label, content, published)
SELECT id, 'v1.0', '<h2>Purpose</h2><p>This Acceptable Use Policy applies to every account, website, file, domain, and service made available through InstaWebMW.</p><h2>Your responsibility</h2><p>You are responsible for content uploaded to your account, the permissions you have to use it, and the activity of people you allow to access your account. Keep credentials secure and promptly report suspected unauthorised access.</p><h2>Prohibited use</h2><p>You may not use the service to break applicable law; infringe intellectual-property or privacy rights; distribute malware, phishing pages, spam, or deceptive material; interfere with service security or availability; probe or bypass access controls; host exploit material; or publish content that is unlawful, abusive, or harmful.</p><h2>Enforcement</h2><p>We may investigate reports, restrict access, remove or disable material, suspend an account, and preserve or disclose information where required by law or reasonably necessary to protect the service, users, or others. Action is taken in context and does not waive any rights.</p><h2>Reports</h2><p>Report suspected misuse to the contact address listed in the Privacy Policy. Include the relevant website address, a clear description, and supporting information.</p>', 1
FROM legal_policies WHERE policy_type = 'acceptable_use'
  AND NOT EXISTS (SELECT 1 FROM legal_policy_versions v WHERE v.policy_id = legal_policies.id AND v.published = 1);

INSERT INTO legal_policy_versions (policy_id, version_label, content, published)
SELECT id, 'v1.0', '<h2>Copyright complaints</h2><p>We respect intellectual-property rights and respond to substantiated complaints about content hosted by users. This policy is a notice-and-takedown process; it does not promise any particular legal safe-harbour or outcome.</p><h2>How to submit a notice</h2><p>Send a written notice to the contact address listed in the Privacy Policy. Include: your name and contact details; identification of the copyrighted work; the exact URL or other location of the allegedly infringing material; a statement that you have a good-faith belief the use is not authorised; a statement that the notice is accurate and that you are authorised to act; and your physical or electronic signature.</p><h2>Our response</h2><p>We may request more information, notify the account holder, restrict or remove access to material, or decline a notice that is incomplete or unsupported. We may take action against repeat or serious infringement.</p><h2>Counter-notices</h2><p>If you believe material was removed in error, contact us with the material location, your explanation and supporting evidence, your contact details, and a statement consenting to the applicable legal process. We will assess the request and may restore material where appropriate.</p><h2>Misrepresentation</h2><p>Do not submit knowingly false, misleading, or abusive notices or counter-notices. This policy does not replace any rights or remedies available under applicable law.</p>', 1
FROM legal_policies WHERE policy_type = 'copyright'
  AND NOT EXISTS (SELECT 1 FROM legal_policy_versions v WHERE v.policy_id = legal_policies.id AND v.published = 1);

INSERT INTO platform_settings (setting_key, setting_value, setting_group)
VALUES ('footer_legal_links', 'Privacy Policy | /privacy\nTerms | /terms\nCookie Policy | /cookie-policy\nAcceptable Use | /acceptable-use\nCopyright & Takedown | /copyright', 'footer')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = CURRENT_TIMESTAMP;

-- Publish a versioned privacy supplement naming the current processors and
-- limiting product-improvement use of data. Existing content remains intact.
INSERT INTO legal_policy_versions (policy_id, version_label, content, published)
SELECT p.id, 'v1.1', CONCAT(v.content, '<h2>Service providers and platform improvement</h2><p>We use PayChangu to process payments. Payment-card, mobile-money, and similar payment credentials are handled by PayChangu, not stored by InstaWebMW. We use Google SMTP only to send service emails such as account verification, password resets, receipts, and operational notices.</p><p>We may use account, device, security, and service-usage information to operate, secure, troubleshoot, and improve the platform. Where feasible, product-improvement analysis uses aggregated or de-identified information. We do not sell personal information or use identifiable uploaded website content to train public models or for unrelated advertising.</p><p>We will process personal data only for purposes permitted by applicable law and subject to appropriate safeguards. Contact us using the details in this policy to make a privacy request or ask about this processing.</p>'), 1
FROM legal_policies p
JOIN legal_policy_versions v ON v.policy_id = p.id AND v.published = 1
WHERE p.policy_type = 'privacy'
  AND NOT EXISTS (SELECT 1 FROM legal_policy_versions existing WHERE existing.policy_id = p.id AND existing.version_label = 'v1.1');

UPDATE legal_policy_versions v
JOIN legal_policies p ON p.id = v.policy_id
SET v.published = CASE WHEN v.version_label = 'v1.1' THEN 1 ELSE 0 END
WHERE p.policy_type = 'privacy' AND v.version_label IN ('v1.0', 'v1.1');

COMMIT;
