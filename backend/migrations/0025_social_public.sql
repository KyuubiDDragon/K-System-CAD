-- Enable public reading for the social rollout, including existing installations.
-- Administrators may change this independently afterwards.
UPDATE kdd_social_settings
SET settings=JSON_SET(settings, '$.guest', JSON_EXTRACT('true', '$')), revision=revision+1
WHERE id=1 AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.guest')), 'false') <> 'true';
