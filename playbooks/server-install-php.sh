#!/usr/bin/env bash

#
# PHP Installation Playbook - Ubuntu/Debian Only
#
# Install specified PHP version with FPM and common extensions
# ----
#
# This playbook only supports Ubuntu and Debian distributions (debian family).
# Both distributions use apt package manager and follow debian conventions.
#
# Required Environment Variables:
#   DEPLOYER_OUTPUT_FILE     - Output file path
#   DEPLOYER_DISTRO          - Exact distribution: ubuntu|debian
#   DEPLOYER_PERMS           - Permissions: root|sudo
#   DEPLOYER_PHP_VERSION     - PHP version to install (e.g., 8.4, 8.3, 7.4)
#   DEPLOYER_PHP_SET_DEFAULT - Set as system default: true|false
#
# Returns YAML with:
#   - status: success
#   - php_version: installed PHP version
#   - is_default: whether this version is set as system default
#   - fpm_socket_path: path to PHP-FPM socket
#   - tasks_completed: list of completed tasks
#

set -o pipefail
export DEBIAN_FRONTEND=noninteractive

[[ -z $DEPLOYER_OUTPUT_FILE ]] && echo "Error: DEPLOYER_OUTPUT_FILE required" && exit 1
[[ -z $DEPLOYER_DISTRO ]] && echo "Error: DEPLOYER_DISTRO required" && exit 1
[[ -z $DEPLOYER_PERMS ]] && echo "Error: DEPLOYER_PERMS required" && exit 1
[[ -z $DEPLOYER_PHP_VERSION ]] && echo "Error: DEPLOYER_PHP_VERSION required" && exit 1
[[ -z $DEPLOYER_PHP_SET_DEFAULT ]] && echo "Error: DEPLOYER_PHP_SET_DEFAULT required" && exit 1
export DEPLOYER_PERMS

# ----
# Helpers
# ----

#
# Permission Management
# ----

#
# Execute command with appropriate permissions

run_cmd() {
	if [[ $DEPLOYER_PERMS == 'root' ]]; then
		"$@"
	else
		sudo -n "$@"
	fi
}

#
# Package Management
# ----

#
# Wait for dpkg lock to be released

wait_for_dpkg_lock() {
	local max_wait=60
	local waited=0
	local lock_found=false

	# Check multiple times to catch the lock even in race conditions
	while ((waited < max_wait)); do
		# Try to acquire the lock by checking if we can open it
		if fuser /var/lib/dpkg/lock-frontend > /dev/null 2>&1 \
			|| fuser /var/lib/dpkg/lock > /dev/null 2>&1 \
			|| fuser /var/lib/apt/lists/lock > /dev/null 2>&1; then
			lock_found=true
			echo "✓ Waiting for package manager lock to be released..."
			sleep 2
			waited=$((waited + 2))
		else
			# Lock not held, but wait a bit to ensure it's really released
			if [[ $lock_found == true ]]; then
				# Was locked before, give it extra time
				sleep 2
			else
				# Never saw lock, just a small delay
				sleep 1
			fi
			return 0
		fi
	done

	echo "Error: Timeout waiting for dpkg lock to be released" >&2
	return 1
}

#
# apt-get with retry

apt_get_with_retry() {
	local max_attempts=5
	local attempt=1
	local wait_time=10
	local output

	while ((attempt <= max_attempts)); do
		# Capture output to check for lock errors
		output=$(run_cmd apt-get "$@" 2>&1)
		local exit_code=$?

		if ((exit_code == 0)); then
			[[ -n $output ]] && echo "$output"
			return 0
		fi

		# Only retry on lock-related errors
		if echo "$output" | grep -qE 'Could not get lock|dpkg.*lock|Unable to acquire'; then
			if ((attempt < max_attempts)); then
				echo "✓ Package manager locked, waiting ${wait_time}s before retry (attempt ${attempt}/${max_attempts})..."
				sleep "$wait_time"
				wait_time=$((wait_time + 5))
				attempt=$((attempt + 1))
				wait_for_dpkg_lock || true
			else
				echo "$output" >&2
				return "$exit_code"
			fi
		else
			# Non-lock error, fail immediately
			echo "$output" >&2
			return "$exit_code"
		fi
	done

	return 1
}

# ----
# Installation Functions
# ----

#
# Repository Setup
# ----

#
# Setup PHP repository

setup_php_repository() {
	echo "✓ Setting up PHP repository..."

	case $DEPLOYER_DISTRO in
		ubuntu)
			# PHP PPA (Ubuntu only)
			if ! grep -qr "ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d/ 2> /dev/null; then
				if ! run_cmd env DEBIAN_FRONTEND=noninteractive add-apt-repository -y ppa:ondrej/php 2>&1; then
					echo "Error: Failed to add PHP PPA" >&2
					exit 1
				fi
			fi
			;;
		debian)
			# Sury PHP repository (Debian only)
			if ! [[ -f /usr/share/keyrings/php-sury-archive-keyring.gpg ]]; then
				if ! curl -fsSL 'https://packages.sury.org/php/apt.gpg' | run_cmd gpg --batch --yes --dearmor -o /usr/share/keyrings/php-sury-archive-keyring.gpg; then
					echo "Error: Failed to add Sury PHP GPG key" >&2
					exit 1
				fi
			fi

			if ! [[ -f /etc/apt/sources.list.d/php-sury.list ]]; then
				local debian_codename
				debian_codename=$(lsb_release -sc)
				if ! echo "deb [signed-by=/usr/share/keyrings/php-sury-archive-keyring.gpg] https://packages.sury.org/php/ ${debian_codename} main" | run_cmd tee /etc/apt/sources.list.d/php-sury.list > /dev/null; then
					echo "Error: Failed to add Sury PHP repository" >&2
					exit 1
				fi
			fi
			;;
	esac
}

#
# Package Installation
# ----

#
# Install PHP packages for specified version

install_php_packages() {
	echo "✓ Installing PHP ${DEPLOYER_PHP_VERSION}..."

	# Update package lists
	echo "✓ Updating package lists..."
	if ! apt_get_with_retry update -q; then
		echo "Error: Failed to update package lists" >&2
		exit 1
	fi

	# Install PHP packages
	if ! apt_get_with_retry install -y -q --no-install-recommends \
		php${DEPLOYER_PHP_VERSION}-cli \
		php${DEPLOYER_PHP_VERSION}-fpm \
		php${DEPLOYER_PHP_VERSION}-common \
		php${DEPLOYER_PHP_VERSION}-opcache \
		php${DEPLOYER_PHP_VERSION}-bcmath \
		php${DEPLOYER_PHP_VERSION}-curl \
		php${DEPLOYER_PHP_VERSION}-mbstring \
		php${DEPLOYER_PHP_VERSION}-xml \
		php${DEPLOYER_PHP_VERSION}-zip \
		php${DEPLOYER_PHP_VERSION}-gd \
		php${DEPLOYER_PHP_VERSION}-intl \
		php${DEPLOYER_PHP_VERSION}-soap 2>&1; then
		echo "Error: Failed to install PHP ${DEPLOYER_PHP_VERSION} packages" >&2
		exit 1
	fi
}

#
# PHP-FPM Configuration
# ----

#
# Configure PHP-FPM for the installed version

configure_php_fpm() {
	echo "✓ Configuring PHP-FPM..."

	local pool_config="/etc/php/${DEPLOYER_PHP_VERSION}/fpm/pool.d/www.conf"

	# Set socket ownership so Caddy can access it
	if ! run_cmd sed -i 's/^;listen.owner = .*/listen.owner = caddy/' "$pool_config"; then
		echo "Error: Failed to set PHP-FPM socket owner" >&2
		exit 1
	fi
	if ! run_cmd sed -i 's/^;listen.group = .*/listen.group = caddy/' "$pool_config"; then
		echo "Error: Failed to set PHP-FPM socket group" >&2
		exit 1
	fi
	if ! run_cmd sed -i 's/^;listen.mode = .*/listen.mode = 0660/' "$pool_config"; then
		echo "Error: Failed to set PHP-FPM socket mode" >&2
		exit 1
	fi

	# Enable PHP-FPM status page
	if ! run_cmd sed -i 's/^;pm.status_path = .*/pm.status_path = \/fpm-status/' "$pool_config"; then
		echo "Error: Failed to enable PHP-FPM status page" >&2
		exit 1
	fi

	# Enable and start PHP-FPM service
	if ! systemctl is-enabled --quiet php${DEPLOYER_PHP_VERSION}-fpm 2> /dev/null; then
		if ! run_cmd systemctl enable --quiet php${DEPLOYER_PHP_VERSION}-fpm; then
			echo "Error: Failed to enable PHP-FPM service" >&2
			exit 1
		fi
	fi
	if ! systemctl is-active --quiet php${DEPLOYER_PHP_VERSION}-fpm 2> /dev/null; then
		if ! run_cmd systemctl start php${DEPLOYER_PHP_VERSION}-fpm; then
			echo "Error: Failed to start PHP-FPM service" >&2
			exit 1
		fi
	fi
}

#
# Default Version Configuration
# ----

#
# Set PHP version as system default

set_as_default() {
	if [[ $DEPLOYER_PHP_SET_DEFAULT != 'true' ]]; then
		return 0
	fi

	echo "✓ Setting PHP ${DEPLOYER_PHP_VERSION} as system default..."

	# Set alternatives for php binaries
	if command -v update-alternatives > /dev/null 2>&1; then
		if run_cmd update-alternatives --set php /usr/bin/php${DEPLOYER_PHP_VERSION} 2> /dev/null; then
			echo "✓ Set php alternative"
		fi

		if run_cmd update-alternatives --set php-config /usr/bin/php-config${DEPLOYER_PHP_VERSION} 2> /dev/null; then
			echo "✓ Set php-config alternative"
		fi

		if run_cmd update-alternatives --set phpize /usr/bin/phpize${DEPLOYER_PHP_VERSION} 2> /dev/null; then
			echo "✓ Set phpize alternative"
		fi
	fi
}

#
# Caddy Configuration
# ----

#
# Update Caddy localhost configuration with PHP-FPM endpoint

update_caddy_config() {
	if ! [[ -f /etc/caddy/conf.d/localhost.caddy ]]; then
		echo "Warning: localhost.caddy not found, skipping Caddy configuration"
		return 0
	fi

	echo "✓ Updating Caddy localhost configuration..."

	# Check if the base server block exists
	if ! grep -q "http://localhost:9001" /etc/caddy/conf.d/localhost.caddy 2> /dev/null; then
		# Create base server block structure
		if ! run_cmd tee /etc/caddy/conf.d/localhost.caddy > /dev/null <<- 'EOF'; then
			# PHP-FPM status endpoints - localhost only (not accessible from internet)
			http://localhost:9001 {
			}
		EOF
			echo "Error: Failed to create Caddy localhost configuration" >&2
			exit 1
		fi
	fi

	# Check if this PHP version's endpoint already exists
	if grep -q "handle_path /php${DEPLOYER_PHP_VERSION}/" /etc/caddy/conf.d/localhost.caddy 2> /dev/null; then
		echo "✓ PHP ${DEPLOYER_PHP_VERSION} endpoint already configured"
		return 0
	fi

	# Create temporary file with the new handle block
	local temp_handle
	temp_handle=$(mktemp)

	cat > "$temp_handle" <<- EOF
		handle_path /php${DEPLOYER_PHP_VERSION}/* {
			reverse_proxy unix//run/php/php${DEPLOYER_PHP_VERSION}-fpm.sock {
				transport fastcgi {
					env SCRIPT_FILENAME /fpm-status
					env SCRIPT_NAME /fpm-status
				}
			}
		}
	EOF

	# Insert the handle block before the closing brace of the server block
	local temp_config
	temp_config=$(mktemp)

	# Read the file, insert handle block before last closing brace
	if ! awk -v handle="$(cat "$temp_handle")" '
		/^}$/ && !found {
			print handle
			found=1
		}
		{ print }
	' /etc/caddy/conf.d/localhost.caddy > "$temp_config"; then
		rm -f "$temp_handle" "$temp_config"
		echo "Error: Failed to update Caddy configuration" >&2
		exit 1
	fi

	# Replace the original file
	if ! run_cmd cp "$temp_config" /etc/caddy/conf.d/localhost.caddy; then
		rm -f "$temp_handle" "$temp_config"
		echo "Error: Failed to write Caddy configuration" >&2
		exit 1
	fi

	rm -f "$temp_handle" "$temp_config"

	# Reload Caddy to apply changes
	if systemctl is-active --quiet caddy 2> /dev/null; then
		if ! run_cmd systemctl reload caddy 2> /dev/null; then
			echo "Warning: Failed to reload Caddy configuration"
		fi
	fi
}

# ----
# Main Execution
# ----

main() {
	local fpm_socket_path="/run/php/php${DEPLOYER_PHP_VERSION}-fpm.sock"
	local is_default="false"

	# Execute installation tasks
	setup_php_repository
	install_php_packages
	configure_php_fpm
	set_as_default
	update_caddy_config

	if [[ $DEPLOYER_PHP_SET_DEFAULT == 'true' ]]; then
		is_default="true"
	fi

	# Get actual PHP version
	local php_version
	php_version=$(php${DEPLOYER_PHP_VERSION} -r "echo PHP_VERSION;" 2> /dev/null || echo "$DEPLOYER_PHP_VERSION")

	# Write output YAML
	if ! cat > "$DEPLOYER_OUTPUT_FILE" <<- EOF; then
		status: success
		php_version: $php_version
		is_default: $is_default
		fpm_socket_path: $fpm_socket_path
		tasks_completed:
		  - setup_php_repository
		  - install_php_packages
		  - configure_php_fpm
		  - set_as_default
		  - update_caddy_config
	EOF
		echo "Error: Failed to write output file" >&2
		exit 1
	fi
}

main "$@"
