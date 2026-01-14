#!/usr/bin/env bats

# AWS Integration Tests
# Tests: pro:aws:key:add, pro:aws:key:list, pro:aws:key:delete, pro:aws:provision
#
# Prerequisites:
#   - AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION in environment
#   - Valid AWS credentials with EC2 permissions
#   - SSH private key at ~/.ssh/id_ed25519

load 'lib/helpers'
load 'lib/pro-helpers'

# ----
# Setup/Teardown
# ----

setup_file() {
	# Skip all tests if AWS credentials not configured
	if ! aws_credentials_available; then
		skip "AWS credentials not configured"
	fi

	# Clean up any leftover test key from previous runs
	aws_cleanup_test_key
}

setup() {
	# Skip individual test if credentials unavailable
	if ! aws_credentials_available; then
		skip "AWS credentials not configured"
	fi
}

# ----
# pro:aws:key:add
# ----

@test "pro:aws:key:add uploads public key to AWS" {
	run_deployer pro:aws:key:add \
		--name="$AWS_TEST_KEY_NAME" \
		--public-key-path="$PRO_TEST_KEY_PATH"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Key pair imported successfully"
	assert_output_contains "Name: $AWS_TEST_KEY_NAME"
	assert_command_replay "pro:aws:key:add"
}

# ----
# pro:aws:key:list
# ----

@test "pro:aws:key:list shows uploaded key" {
	run_deployer pro:aws:key:list

	debug_output

	[ "$status" -eq 0 ]
	assert_output_contains "$AWS_TEST_KEY_NAME"
	assert_command_replay "pro:aws:key:list"
}

# ----
# pro:aws:key:delete
# ----

@test "pro:aws:key:delete removes key from AWS" {
	run_deployer pro:aws:key:delete \
		--key="$AWS_TEST_KEY_NAME" \
		--force \
		--yes

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Key pair deleted successfully"
	assert_command_replay "pro:aws:key:delete"
}

@test "pro:aws:key:list confirms key deleted" {
	run_deployer pro:aws:key:list

	debug_output

	[ "$status" -eq 0 ]
	assert_output_not_contains "$AWS_TEST_KEY_NAME"
}

# ----
# pro:aws:provision
# ----

@test "pro:aws:provision creates EC2 instance and adds to inventory" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Cleanup any leftover test server
	aws_cleanup_test_server

	run_deployer pro:aws:provision \
		--name="$AWS_TEST_SERVER_NAME" \
		--instance-type="$AWS_TEST_INSTANCE_TYPE" \
		--ami="$AWS_TEST_AMI" \
		--key-pair="$AWS_TEST_KEY_PAIR" \
		--private-key-path="$AWS_TEST_PRIVATE_KEY_PATH" \
		--vpc="$AWS_TEST_VPC" \
		--subnet="$AWS_TEST_SUBNET" \
		--disk-size="$AWS_TEST_DISK_SIZE" \
		--no-monitoring

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Instance provisioned"
	assert_output_contains "Instance is running"
	assert_output_contains "Elastic IP allocated"
	assert_output_contains "Server added to inventory"
	assert_command_replay "pro:aws:provision"
}

@test "server:install configures AWS provisioned server" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Full install takes time - use longer timeout
	run timeout 600 "$DEPLOYER_BIN" --inventory="$TEST_INVENTORY" server:install \
		--server="$AWS_TEST_SERVER_NAME" \
		--generate-deploy-key \
		--php-version="$PRO_TEST_PHP_VERSION" \
		--php-extensions="$PRO_TEST_PHP_EXTENSIONS"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Server installation completed"
	assert_output_contains "public key"
	assert_command_replay "server:install"
}

# ----
# pro:aws:dns:set
# ----

@test "pro:aws:dns:set creates A record for root domain" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Get server IP from inventory
	local server_ip
	server_ip=$(get_server_ip "$AWS_TEST_SERVER_NAME")

	[[ -n "$server_ip" ]] || skip "Could not determine server IP"

	run_deployer pro:aws:dns:set \
		--zone="$AWS_TEST_HOSTED_ZONE" \
		--type="A" \
		--name="@" \
		--value="$server_ip" \
		--ttl="60"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record upserted successfully"
	assert_command_replay "pro:aws:dns:set"
}

@test "pro:aws:dns:set creates A record for www subdomain" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Get server IP from inventory
	local server_ip
	server_ip=$(get_server_ip "$AWS_TEST_SERVER_NAME")

	[[ -n "$server_ip" ]] || skip "Could not determine server IP"

	run_deployer pro:aws:dns:set \
		--zone="$AWS_TEST_HOSTED_ZONE" \
		--type="A" \
		--name="www" \
		--value="$server_ip" \
		--ttl="60"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record upserted successfully"
	assert_command_replay "pro:aws:dns:set"
}

# ----
# pro:cf:dns:set
# ----

@test "pro:cf:dns:set creates A record for root domain (proxied)" {
	# Skip if AWS credentials/SSH key not available OR Cloudflare credentials missing
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi
	if ! cf_credentials_available; then
		skip "Cloudflare credentials not configured"
	fi

	# Get server IP from inventory
	local server_ip
	server_ip=$(get_server_ip "$AWS_TEST_SERVER_NAME")

	[[ -n "$server_ip" ]] || skip "Could not determine server IP"

	run_deployer pro:cf:dns:set \
		--zone="$CF_TEST_DOMAIN" \
		--type="A" \
		--name="@" \
		--value="$server_ip" \
		--ttl="60" \
		--proxied

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record"
	assert_output_contains "successfully"
	assert_command_replay "pro:cf:dns:set"
}

@test "pro:cf:dns:set creates A record for www subdomain (non-proxied)" {
	# Skip if AWS credentials/SSH key not available OR Cloudflare credentials missing
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi
	if ! cf_credentials_available; then
		skip "Cloudflare credentials not configured"
	fi

	# Get server IP from inventory
	local server_ip
	server_ip=$(get_server_ip "$AWS_TEST_SERVER_NAME")

	[[ -n "$server_ip" ]] || skip "Could not determine server IP"

	run_deployer pro:cf:dns:set \
		--zone="$CF_TEST_DOMAIN" \
		--type="A" \
		--name="www" \
		--value="$server_ip" \
		--ttl="60" \
		--no-proxied

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record"
	assert_output_contains "successfully"
	assert_command_replay "pro:cf:dns:set"
}

# ----
# pro:cf:dns:list
# ----

@test "pro:cf:dns:list shows created records" {
	# Skip if AWS credentials/SSH key not available OR Cloudflare credentials missing
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi
	if ! cf_credentials_available; then
		skip "Cloudflare credentials not configured"
	fi

	run_deployer pro:cf:dns:list \
		--zone="$CF_TEST_DOMAIN"

	debug_output

	[ "$status" -eq 0 ]
	assert_output_contains "$CF_TEST_DOMAIN"
	assert_command_replay "pro:cf:dns:list"
}

# ----
# pro:cf:dns:delete
# ----

@test "pro:cf:dns:delete removes www A record" {
	# Skip if AWS credentials/SSH key not available OR Cloudflare credentials missing
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi
	if ! cf_credentials_available; then
		skip "Cloudflare credentials not configured"
	fi

	run_deployer pro:cf:dns:delete \
		--zone="$CF_TEST_DOMAIN" \
		--type="A" \
		--name="www" \
		--force \
		--yes

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record deleted successfully"
	assert_command_replay "pro:cf:dns:delete"
}

@test "pro:cf:dns:delete removes root A record" {
	# Skip if AWS credentials/SSH key not available OR Cloudflare credentials missing
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi
	if ! cf_credentials_available; then
		skip "Cloudflare credentials not configured"
	fi

	run_deployer pro:cf:dns:delete \
		--zone="$CF_TEST_DOMAIN" \
		--type="A" \
		--name="@" \
		--force \
		--yes

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "DNS record deleted successfully"
	assert_command_replay "pro:cf:dns:delete"
}

# ----
# site:create
# ----

@test "site:create creates site on AWS provisioned server" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Cleanup any leftover test site
	cleanup_test_site "$AWS_TEST_DOMAIN"

	run_deployer site:create \
		--domain="$AWS_TEST_DOMAIN" \
		--server="$AWS_TEST_SERVER_NAME" \
		--php-version="$PRO_TEST_PHP_VERSION" \
		--www-mode="redirect-to-root" \
		--web-root="/"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "added to inventory"
	assert_command_replay "site:create"
}

# ----
# site:shared:push
# ----

@test "site:shared:push uploads .env to AWS site" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	run_deployer site:shared:push \
		--domain="$AWS_TEST_DOMAIN" \
		--local="${BATS_TEST_ROOT}/fixtures/env/deploy-me.env" \
		--remote=".env"

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Shared file uploaded"
	assert_command_replay "site:shared:push"
}

# ----
# site:deploy
# ----

@test "site:deploy deploys application to AWS site" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Deploy takes time - use longer timeout
	run timeout 300 "$DEPLOYER_BIN" --inventory="$TEST_INVENTORY" site:deploy \
		--domain="$AWS_TEST_DOMAIN" \
		--repo="$PRO_TEST_DEPLOY_REPO" \
		--branch="$PRO_TEST_DEPLOY_BRANCH" \
		--yes

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Deployment completed"
	assert_command_replay "site:deploy"
}

# ----
# HTTP Verification
# ----

@test "deployed AWS site responds to HTTP requests" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	# Get server IP to bypass DNS (faster than waiting for propagation)
	local server_ip
	server_ip=$(get_server_ip "$AWS_TEST_SERVER_NAME")

	# Wait for HTTP response containing our test message (30 seconds - should be immediate with direct IP)
	wait_for_http "$AWS_TEST_DOMAIN" "$PRO_TEST_APP_MESSAGE" 30 "$server_ip"
}

# ----
# Cleanup
# ----

@test "server:delete removes AWS instance and cleans up resources" {
	# Skip if AWS credentials or SSH key not available
	if ! aws_provision_config_available; then
		skip "AWS credentials not configured or SSH key missing"
	fi

	run_deployer server:delete \
		--server="$AWS_TEST_SERVER_NAME" \
		--force \
		--yes

	debug_output

	[ "$status" -eq 0 ]
	assert_success_output
	assert_output_contains "Instance terminated"
	assert_output_contains "Elastic IP released"
	assert_output_contains "removed from inventory"
	assert_command_replay "server:delete"
}
