FROM maven:3.9-eclipse-temurin-17 AS build


WORKDIR /app


COPY pom.xml .


RUN mvn dependency:go-offline


COPY src ./src






RUN mvn package -DskipTests


RUN ls -l /app/target


FROM eclipse-temurin:17-jre-alpine


WORKDIR /app



RUN mkdir -p output && chmod 777 /app/output



COPY --from=build /app/target/reporting-app*.jar ./app.jar


RUN ls -l /app


RUN chmod +rx /app/app.jar


# Create crontab file to run daily at 2 AM
# Redirect stdout/stderr to Docker logs (pid 1's stdout/stderr)
RUN echo "0 2 * * * java -jar /app/app.jar >> /proc/1/fd/1 2>&1" > /etc/cron.d/reporting-cron


CMD java -jar /app/app.jar 
