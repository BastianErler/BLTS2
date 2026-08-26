-- BLTS2: Ergänzt die im neuen Dump fehlenden Spiele der Saison 2025/26.
-- Quelle: altes games-Schema, ausschließlich Spiele mit Eisbären-ID 4.
-- Die Spiele 1-629 sind im neuen Dump bereits vorhanden; importiert werden IDs 630-683.
-- Team-IDs werden aus dem neuen teams-Seed übernommen (Eisbären Berlin = 4).
-- Vor dem Ausführen unbedingt ein Backup der Ziel-Datenbank erstellen.

START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;

-- Saison 2025/26 anlegen, falls sie im Ziel noch nicht existiert.
INSERT INTO seasons
    (id, name, start_date, end_date, is_active, phase_1_multiplier, phase_2_multiplier, phase_3_multiplier, playoff_multiplier, created_at, updated_at)
SELECT 9, '25/26', '2025-09-01', '2026-05-01', 1, 1.0, 1.5, 2.0, 3.0, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM seasons WHERE id = 9 OR name = '25/26');

-- Alte home_team_id/away_team_id werden in opponent_id + is_home umgebaut.
-- Alte home_goals/away_goals werden in eisbaeren_goals + opponent_goals umgebaut.
INSERT INTO games
    (id, game_number, opponent_id, season_id, matchday, is_home, kickoff_at,
     eisbaeren_goals, opponent_goals, status, needs_review, is_derby, is_playoff,
     difficulty_rating, email_reminder_sent, sms_reminder_sent, created_at, updated_at)
VALUES
(630, 1, 7, 9, NULL, 0, '2025-09-14 16:30:00', 7, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(631, 2, 9, 9, NULL, 0, '2025-09-19 19:30:00', 1, 7, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(632, 3, 2, 9, NULL, 0, '2025-09-21 14:00:00', 1, 5, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(633, 4, 12, 9, NULL, 1, '2025-09-26 19:30:00', 2, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(634, 5, 13, 9, NULL, 1, '2025-09-28 14:00:00', 1, 2, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(635, 6, 1, 9, NULL, 0, '2025-10-03 14:00:00', 4, 3, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(636, 7, 6, 9, NULL, 1, '2025-10-05 14:00:00', 3, 0, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(637, 8, 16, 9, NULL, 0, '2025-10-10 19:30:00', 3, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(638, 9, 11, 9, NULL, 1, '2025-10-12 14:00:00', 3, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(639, 10, 14, 9, NULL, 1, '2025-10-17 19:30:00', 3, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(640, 11, 5, 9, NULL, 0, '2025-10-19 14:00:00', 4, 3, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(641, 12, 10, 9, NULL, 0, '2025-10-23 19:30:00', 2, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(642, 13, 1, 9, NULL, 1, '2025-10-25 20:00:00', 3, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(643, 14, 9, 9, NULL, 1, '2025-10-29 19:30:00', 4, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(644, 15, 13, 9, NULL, 0, '2025-10-31 19:30:00', 4, 3, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(645, 16, 2, 9, NULL, 1, '2025-11-02 14:00:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(646, 17, 11, 9, NULL, 0, '2025-11-14 19:30:00', 5, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(647, 18, 16, 9, NULL, 1, '2025-11-16 19:00:00', 4, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(648, 19, 10, 9, NULL, 1, '2025-11-21 19:30:00', 1, 3, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(649, 20, 12, 9, NULL, 0, '2025-11-23 16:30:00', 4, 2, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(650, 21, 6, 9, NULL, 0, '2025-11-26 19:30:00', 2, 5, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(651, 22, 14, 9, NULL, 0, '2025-11-28 19:30:00', 6, 1, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(652, 23, 7, 9, NULL, 1, '2025-11-30 14:00:00', 3, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(653, 24, 5, 9, NULL, 1, '2025-12-05 19:30:00', 5, 3, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(654, 25, 12, 9, NULL, 0, '2025-12-12 19:30:00', 3, 5, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(655, 26, 13, 9, NULL, 1, '2025-12-14 14:00:00', 2, 0, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(656, 27, 6, 9, NULL, 0, '2025-12-18 19:30:00', 5, 8, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(657, 28, 7, 9, NULL, 1, '2025-12-21 19:00:00', 3, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(658, 29, 9, 9, NULL, 0, '2025-12-23 19:30:00', 5, 8, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(659, 30, 14, 9, NULL, 1, '2025-12-26 16:30:00', 1, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(660, 31, 11, 9, NULL, 1, '2025-12-28 14:00:00', 5, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(661, 32, 2, 9, NULL, 0, '2025-12-30 19:30:00', 4, 6, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(662, 33, 16, 9, NULL, 1, '2026-01-02 19:30:00', 2, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(663, 34, 1, 9, NULL, 1, '2026-01-04 14:00:00', 6, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(664, 35, 10, 9, NULL, 0, '2026-01-06 16:30:00', 3, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(665, 36, 5, 9, NULL, 0, '2026-01-08 19:30:00', 3, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(666, 37, 13, 9, NULL, 0, '2026-01-15 19:30:00', 5, 3, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(667, 38, 14, 9, NULL, 0, '2026-01-18 14:00:00', 2, 5, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(668, 39, 6, 9, NULL, 1, '2026-01-23 19:30:00', 2, 1, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(669, 40, 16, 9, NULL, 0, '2026-01-25 16:30:00', 1, 4, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(670, 41, 1, 9, NULL, 0, '2026-01-27 19:30:00', 1, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(671, 42, 12, 9, NULL, 1, '2026-02-25 19:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(672, 43, 5, 9, NULL, 1, '2026-02-27 19:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(673, 44, 2, 9, NULL, 1, '2026-03-01 14:00:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(674, 45, 11, 9, NULL, 0, '2026-03-06 19:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(675, 46, 7, 9, NULL, 0, '2026-03-08 14:00:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(676, 47, 9, 9, NULL, 1, '2026-03-13 07:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(677, 48, 10, 9, NULL, 1, '2026-03-15 14:00:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(678, 49, 9, 9, NULL, 1, '2026-03-13 19:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(679, 50, 17, 9, NULL, 1, '2025-09-09 19:30:00', 6, 2, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(680, 51, 17, 9, NULL, 1, '2025-12-07 14:00:00', 5, 1, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW()),
(681, 52, 17, 9, NULL, 0, '2026-01-10 17:00:00', 3, 2, 'finished', 0, 0, 0, 3, 1, 1, NOW(), NOW()),
(682, 53, 17, 9, NULL, 0, '2026-03-04 19:30:00', NULL, NULL, 'scheduled', 0, 0, 0, 3, 0, 0, NOW(), NOW()),
(683, 54, 2, 9, NULL, 0, '2025-11-02 16:30:00', 4, 1, 'finished', 0, 0, 0, 3, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE id = id;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

