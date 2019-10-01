package main

import (
	"log"
	"os"
	"os/exec"
	"strconv"
	"strings"
	"text/template"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/ssm"
)

// Config struct stores the config needed for the config-local file
type Config struct {
	Debug            int
	URL              string
	CollibraURL      string
	CollibraUser     string
	CollibraPassword string
	ByuAPIKey        string
	ByuAPISecret     string
	DremioURL        string
	DremioUser       string
	DremioPassword   string
	GithubToken      string
	DBHost           string
	DBUser           string
	DBPassword       string
	DBName           string
	Salt             string
	CipherSeed       string
}

func main() {
	log.Println("Starting Config Launcher")
	path := os.Getenv("HANDEL_PARAMETER_STORE_PATH")
	if path == "" {
		log.Println("No Path Enviroment Variable found")
		log.Println("Linking Uploads Directory")
		linkDir()
		run()
	}
	log.Println("Path:", path)

	sess, err := session.NewSessionWithOptions(session.Options{
		Config:            aws.Config{Region: aws.String("us-west-2")},
		SharedConfigState: session.SharedConfigEnable,
	})
	if err != nil {
		log.Fatal(err)
	}
	ssmsvc := ssm.New(sess)

	log.Println("Fetching Parameters at path:", path)
	params := getPathParameters(ssmsvc, &path)
	debug, _ := strconv.Atoi(params["debug"])
	config := Config{
		Debug:            debug,
		URL:              params["url"],
		CollibraURL:      params["collibra_url"],
		CollibraUser:     params["collibra_user"],
		CollibraPassword: params["collibra_password"],
		ByuAPIKey:        params["byuapi_key"],
		ByuAPISecret:     params["byuapi_secret"],
		DremioURL:        params["dremio_url"],
		DremioUser:       params["dremio_user"],
		DremioPassword:   params["dremio_password"],
		GithubToken:      params["github_token"],
		DBHost:           os.Getenv("DB_ADDRESS"),
		DBUser:           params["db_username"],
		DBPassword:       params["db_password"],
		DBName:           params["db_name"],
		Salt:             params["salt"],
		CipherSeed:       params["cipher"],
	}

	log.Println("Creating core-local.php")
	makeTemplate(config)
	log.Println("Linking Uploads Directory")
	linkDir()
	log.Println("Starting Apache2")
	run()
}

func run() {
	//Clean up potential conflict
	os.Remove("/run/apache2/httpd.pid")

	cmd := exec.Command("/usr/sbin/httpd", "-D", "FOREGROUND")
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	err := cmd.Run()
	if err != nil {
		log.Fatal(err)
	}
	log.Println("Finished")
}

func linkDir() {
	efsdir := os.Getenv("STORAGE_MOUNT_DIR")
	efsuploadsdir := efsdir + "/" + "uploads"
	efstmpdir := efsdir + "/" + "tmp"
	uploads := "/cake/app/webroot/uploads"
	tmp := "/cake/app/tmp"
	err := os.Symlink(efsuploadsdir, uploads)
	if err != nil {
		log.Println(err)
	}
	err = os.Symlink(efstmpdir, tmp)
	if err != nil {
		log.Println(err)
	}
}

func makeTemplate(config Config) {
	f, err := os.Create("app/Config/core-local.php")
	if err != nil {
		log.Println("Failed to create file: ", err)
	}
	defer f.Close()
	t := template.Must(template.ParseFiles("go_core-local.gophp"))
	t.Execute(f, config)
}

func getPathParameters(ssmsvc *ssm.SSM, path *string) map[string]string {
	m := make(map[string]string, 0)
	err := ssmsvc.GetParametersByPathPages(&ssm.GetParametersByPathInput{
		Path:           path,
		WithDecryption: aws.Bool(true),
	}, func(params *ssm.GetParametersByPathOutput, lastPage bool) bool {
		for _, param := range params.Parameters {
			name := strings.TrimPrefix(*param.Name, *path)
			m[name] = *param.Value
		}
		return !lastPage
	})
	if err != nil {
		log.Fatal(err)
	}
	return m
}

func getParameter(ssmsvc *ssm.SSM, paramName *string) string {
	param, err := ssmsvc.GetParameter(&ssm.GetParameterInput{
		Name:           paramName,
		WithDecryption: aws.Bool(true),
	})
	if err != nil {
		log.Fatal(err)
	}
	return *param.Parameter.Value
}
