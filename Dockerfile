# Use official PHP image with PostgreSQL support
FROM php:8.2-cli

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Python and pip for Python API
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy Python requirements and install
COPY requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt

# Copy all files
COPY . .

# Expose port (Railway will use PORT env var at runtime)
EXPOSE 8080

# Start PHP built-in server (Railway sets PORT env var)
# Note: For production, you might want to run both PHP and Python services
CMD sh -c "php -S 0.0.0.0:\${PORT:-8080} -t . & python3 api/app.py & wait"

