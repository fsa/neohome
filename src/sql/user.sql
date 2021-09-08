CREATE TABLE users (
    uuid uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    login varchar(30) NOT NULL UNIQUE,
    password_hash text,
    name text NOT NULL,
    email text,
    scope text[],
    groups text[],
    disabled boolean NOT NULL DEFAULT false
);
COMMENT ON TABLE users IS 'Пользователи';

CREATE TABLE user_groups (
    name text NOT NULL PRIMARY KEY,
    scope text[],
    description text
);
COMMENT ON TABLE user_groups IS 'Группы доступа';

CREATE TABLE user_scopes (
    name text NOT NULL PRIMARY KEY,
    description text
);
COMMENT ON TABLE user_scopes IS 'Права доступа';

CREATE TABLE user_fail2ban (
    id bigint PRIMARY KEY GENERATED BY DEFAULT AS IDENTITY,
    login varchar(30) NOT NULL,
    ip inet NOT NULL,
    fail_time timestamptz NOT NULL
);
CREATE INDEX user_fail2ban_login_idx ON user_fail2ban (login);
CREATE INDEX user_fail2ban_fail_time_idx ON user_fail2ban (fail_time);
COMMENT ON TABLE user_fail2ban IS 'Данные об ошибках аутентификации';