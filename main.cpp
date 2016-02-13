//VERSION 1.0.0.0
#include <iostream>
//cURLpp
#include <curlpp/cURLpp.hpp>
#include <curlpp/Easy.hpp>
#include <curlpp/Options.hpp>
//strings and string-processing
#include <string.h>
#include <sstream>
//JSONcpp
#include <jsoncpp/json/json.h>
//mySQL Connector/c++
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include "mysql_driver.h"
//timing
#include <ctime>
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
int microSec = 1000000; //used to convert from microseconds to seconds and visa versa
/* */
void storeMapData(const Json::Value map)
{
	const Json::Value objectives = map["objectives"];
	cout << objectives[0]["claimed_at"];
	cout << objectives[0]["claimed_by"];
	cout << objectives[0]["id"];
	cout << objectives[0]["last_flipped"];
	cout << objectives[0]["owner"];
	cout << objectives[0]["type"];
}
/* */
int main (int argc, char *argv[])
{
    try 
    {
		/*
		----------------------------------------------------------
		*/
		/* The request to be sent */
		Easy myRequest;
		/* Set the options */
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches/1-3"));
		/* Perform request */
		myRequest.perform();
		/*
		----------------------------------------------------------
		*/
		Json::Value root;
		Json::Reader reader;
		bool parsingSuccessful = reader.parse(result.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			return 1;
		}
		const Json::Value maps = root["maps"];
		storeMapData(maps[0]);
		/*
		----------------------------------------------------------
		*/
		clock_t begin = clock();
		/* */
		sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
 		sql::ResultSet *res;
		driver = sql::mysql::get_mysql_driver_instance();
		con = driver->connect("tcp://127.0.0.1:3306", "root", "egamirrorimeht");

		stmt = con->createStatement();
		stmt->execute("USE Gw2Analyser");
		res = stmt->executeQuery("SELECT * from user;");
		while (res->next())
		{
			/* Access column data by alias or column name */
			cout << res->getString(1) << endl;
			cout << res->getString(2) << endl;
		}
		delete stmt;
		delete con;
		/* */
		clock_t end = clock();
		double elapsed_msecs = double(end - begin) / CLOCKS_PER_SEC * microSec;
		usleep(microSec*5 - elapsed_msecs);
		/*
		----------------------------------------------------------
		*/
	/*
	----------------------------------------------------------
	*/
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
	return 0;
}
