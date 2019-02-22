package main

import (
	"fmt"
	"log"
	"os"
	"os/exec"
	"strings"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/ssm"
)

func main() {
	log.Println("Starting Config Launcher")
	path := os.Getenv("HANDEL_PARAMETER_STORE_PATH")
	prefix := os.Getenv("HANDEL_PARAMETER_STORE_PREFIX")
	if path == "" || prefix == "" {
		log.Println("No Path/Prefix Enviroment Variable found")
		run()
	}
	prefix = fmt.Sprintf("%s.", prefix)
	log.Println("Path:", path)
	log.Println("Prefix:", prefix)

	config := aws.Config{Region: aws.String("us-west-2")}
	sess, err := session.NewSessionWithOptions(session.Options{
		Config:            config,
		SharedConfigState: session.SharedConfigEnable,
	})
	if err != nil {
		log.Fatal(err)
	}
	ssmsvc := ssm.New(sess)

	log.Println("Fetching Parameters at path:", path)
	getPathParameters(ssmsvc, &path)

	keyname := fmt.Sprintf("%sdb.db_username", prefix)
	log.Println("Fetching Parameter:", keyname)
	getParameter(ssmsvc, &keyname, "CAKE_DEFAULT_DB_USERNAME")

	keyname = fmt.Sprintf("%sdb.db_password", prefix)
	log.Println("Fetching Parameter:", keyname)
	getParameter(ssmsvc, &keyname, "CAKE_DEFAULT_DB_PASSWORD")

	log.Println("Setting DB HOST")
	os.Setenv("CAKE_DEFAULT_DB_HOST", os.Getenv("DB_ADDRESS"))

	log.Println("Starting Apache2")
	run()
}

func run() {
	//Clean up potential conflict
	os.Remove("/run/apache2/httpd.pid")
	cmd := exec.Command("/usr/sbin/httpd", "-D", "FOREGROUND")
	cmd.Stdout = os.Stdout
	err := cmd.Run()
	if err != nil {
		log.Fatal(err)
	}
	log.Println("Finished")
}

func getPathParameters(ssmsvc *ssm.SSM, path *string) {
	params, err := ssmsvc.GetParametersByPath(&ssm.GetParametersByPathInput{
		Path:           path,
		WithDecryption: aws.Bool(true),
	})
	if err != nil {
		log.Fatal(err)
	}
	for _, param := range params.Parameters {
		os.Setenv(strings.TrimPrefix(*param.Name, *path), *param.Value)
	}
}

func getParameter(ssmsvc *ssm.SSM, paramName *string, envKey string) {
	param, err := ssmsvc.GetParameter(&ssm.GetParameterInput{
		Name:           paramName,
		WithDecryption: aws.Bool(true),
	})
	if err != nil {
		log.Fatal(err)
	}
	value := *param.Parameter.Value
	os.Setenv(envKey, value)
}
