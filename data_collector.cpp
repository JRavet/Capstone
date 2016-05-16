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
//multithreading
#include <pthread.h>
//
#define FIRST_SRV "green"
#define SECOND_SRV "blue"
#define THIRD_SRV "red"
#define TIME_RES 60 //in seconds; standard is 60 seconds
#define MICROSEC 1000000.0 //the amount of microseconds in a second
//
#define IPADDR "tcp://127.0.0.1:3306"
#define USERNAME "gw2datacollector"
#define PASSWORD "egamirrorimeht"
#define DATABASE "Gw2Analyser"
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
//
/*
stringstream *converter 	- pointer to the stringstream to use to convert a number to a string
float valueToConvert 		- the number to turn into a string
string *returnString		- the string to append the number to

This function will take a specified pointer to a stringStream, a float (essentially any type of number), and a pointer to a string.
It will insert the value of the valueToConvert into the stringstream using the '<<' operator, and append to the returnString using
the string stream's string. It clears the stringstream afterwards.
NOTE: The stringstream MUST be cleared before usage
*/
void convertNumToString(stringstream *converter, float valueToConvert, string *returnString)
{
	(*converter) << valueToConvert;
	(*returnString) += converter->str();
	converter->str("");
	converter->clear();
}
/* 
string guildID			- the hexcode ID of the guild to check the database (and GW2 API) for
sql::Connection *con	- the database connection object
bool *force_resync		- a boolean which signifies if a forceful resync must occur due to errors

This function takes the given guildID and compares it to all guildIDs in the database.
If there are no matches, it will query the API for the guild info and store the result into the database.
If a match is found in the database, it will do nothing more.
*/
void check_guildClaim(string guildID, sql::Connection *con, bool *force_resync)
{
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement(); //create a statement to be used later
	try
	{
		res = stmt->executeQuery("select * from guild where guild_id=\"" + guildID +"\"");
	} //attempt to query the database to see if a guild with the given guildID exists in it
	catch (sql::SQLException &e)
	{
	//	cout << e.what() << endl;
		return; //return from the function early
	}
	if (!res->next()) //if there were no matching guild ids ...
	{
		Easy request;
		stringstream guildDetails;
		Json::Value guild_data;
		Json::Reader reader;
		/* Create an API request skeleton */
		request.setOpt(cURLpp::Options::WriteStream(&guildDetails));
		request.setOpt(Url("https://api.guildwars2.com/v1/guild_details.json?guild_id=" + guildID));	
		//ask for the guild data using the provided guildID	
		try
		{
			request.perform();
			/* */
			if (reader.parse(guildDetails.str(), guild_data))
			{ //if the API-query was successful and it was parsed properly
				try
				{ //attempt to store the guild info into the database
					stmt->execute("INSERT INTO guild VALUES(\"" + guildID + "\",\"" + guild_data["guild_name"].asString() + "\",\"" + guild_data["tag"].asString() + "\");");
				}
				catch (sql::SQLException &e)
				{
				//	cout << e.what() << endl;
				}
			}
		}
		catch (exception &e)
		{ //an error occurred contacting the API; force a resync on the next loop in the main body
			(*force_resync) = true;
			cout << e.what() << endl;
		}
	}
	delete stmt;
	delete res;
}
/* 
string time1			- The first (earlier) timeStamp to compare. Must be in "YYYY-MM-DDThh:mm:ssz" format (API format)
string time2			- The second (later) timeStamp to compare. Must be in "YYYY-MM-DD hh:mm:ss" format (database format)
string *return_string	- The string to append the difference-in-time to

This function takes two strings, each containing a timeStamp (with formats specified above) and finds the difference in time
	between them. It will append a proper SQL TIME formatted string to the return_string.
*/
void compare_timeStamps(string time1, string time2, string *return_string)
{ //time1 > time2
	stringstream converter;
	struct tm current,previous;
	converter.str("");
	converter.clear();
	strptime(time1.c_str(), "%Y-%m-%dT%H:%M:%SZ", &current); //parses a string into a 'tm' struct; using the API's format
	strptime(time2.c_str(), "%Y-%m-%d %H:%M:%S ", &previous); //parses a string into a 'tm' struct; using a SQL date-time format
	time_t t1 = mktime(&current); //converts a 'tm' struct to a time_t
	time_t t2 = mktime(&previous); //converts a 'tm' struct to a time_t
	(*return_string) = ""; //clear the return string for new input
	int totalSeconds = difftime(t1,t2); //the total time elapsed between timestamps
	int seconds=0,minutes=0,hours=0; //the 'parsed' seconds/minutes/hours
	seconds = totalSeconds % 60; //The number of seconds, between 0 and 59
	minutes = (int) ((totalSeconds / 60) % 60); //The number of minutes, between 0 and 59
	hours = (int) ((totalSeconds / 3600) % 60); //The number of hours, between 0 and the max integer limit
	convertNumToString(&converter,hours,return_string); //append the hours elapsed to the return string
	(*return_string) += ":";
	convertNumToString(&converter,minutes,return_string); //append the minutes elapsed to the return string
	(*return_string) += ":";
	convertNumToString(&converter,seconds,return_string); //append the seconds elapsed to the return string
}
/* 
const Json::Value *match_data	- the trunk of a given match data set
const Json::Value *objectivee	- the specific objective to update duration_owned and duration_claimed values for
sql::connection *con			- the database connection object
string *current_time			- A SQL TIME formatted string containing the current timeStamp value

This function updates an activity_data's duration_owned and duration_claimed attributes whenever the 
	objective has changed claims or ownership.
*/
void update_activityData(const Json::Value *match_data, const Json::Value *objective, sql::Connection *con, string *current_time)
{
	string updateStmt = "";
	stringstream converter;
	try
	{
		sql::Statement *stmt;
		sql::ResultSet *res;
		bool updateRow = false; //initialize to false; its true if there are any updates to be made
		stmt = con->createStatement(); //create a statement object to be used later
		//begin building the query-string
		//we're attempting to find the last activity_data point (by timeStamp) with ...
		//the given match_id, start_time, and obj_id
		//additionally, we're formatting some time-data for use in the query and when comparing date-times
		string previous_data_stmt = "SELECT last_flipped, claimed_at, guild_id FROM activity_data WHERE"; //begin building the SQL statement
		previous_data_stmt += " match_id = \"" + (*match_data)["id"].asString() + "\"";
			string start_time = (*match_data)["start_time"].asString();
			start_time[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
			start_time.erase(19,1); // ^^
			//
			string last_flipped = (*objective)["last_flipped"].asString();
			last_flipped[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
			last_flipped.erase(19,1); // ^^
		previous_data_stmt += " and start_time = \"" + start_time + "\"";
		previous_data_stmt += " and obj_id = \"" + (*objective)["id"].asString() + "\" ORDER BY timeStamp DESC LIMIT 1;";
		//end of query-string building
		res = stmt->executeQuery(previous_data_stmt); //execute the query
		if (res->next())
		{ //if there is a previous entry for the given objective
			string duration_claimed = "00:00:00"; //initialize the attributes to 00:00:00
			string duration_owned = "00:00:00"; //initialize the attributes to 00:00:00
			string previous_claimed_at = res->getString("claimed_at"); //get the previous claimed_at time ...
			string previous_last_flipped = res->getString("last_flipped"); // ... and the previous last_flipped time
			if (previous_claimed_at.compare("0000-00-00 00:00:00") != 0 && res->getString("guild_id").compare((*objective)["claimed_by"].asString()) != 0)
			{ //if the previous duration claimed is NOT "0000-00-00 00:00:00" (ie, it was claimed) and there is a new claim on it
				//calculate claim duration
				string time_claimed = (*current_time); //set the time_claimed to the current time by default
				time_claimed[9] = 'T';
				time_claimed += "Z"; //manually reformatting time_claimed to be in API-format for comparison in compare_timeStamps
				if ((*objective)["claimed_at"].asString().compare("") != 0) //if there is a new claim on it ...
				{ // ... don't use the timestamp to get claim duration; use the real data
					time_claimed = (*objective)["claimed_at"].asString();
				}
				compare_timeStamps(time_claimed, previous_claimed_at, &duration_claimed); //get the difference in times between claims
				updateRow = true; //the row needs to be updated, but more processing must be done ...
			}
			if (last_flipped != previous_last_flipped)
			{ //if the objective has changed ownership since last time
				//calculate owned duration
				compare_timeStamps((*objective)["last_flipped"].asString(), previous_last_flipped, &duration_owned);
				updateRow = true; //the row needs to be updated
			}
			//
			if (updateRow == true) //if there was a change in claims, or ownership, then the row needs to be updated
			{
				updateStmt = "UPDATE activity_data SET duration_claimed = \"" + duration_claimed + "\", duration_owned = \"" + duration_owned + "\" WHERE match_id = \"" + (*match_data)["id"].asString() + "\" and start_time = \"" + start_time + "\" and obj_id = \"" + (*objective)["id"].asString() + "\" ORDER BY timeStamp DESC LIMIT 1;";
				stmt->execute(updateStmt); //update the row
			} //if the row does not need to be updated, then nothing is modified and the function will exit normally
		}		
		delete stmt;
		delete res;
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
}
/*
const Json::Value *match_data	- the trunk of match data for a single match
int mapNum						- the map index number (0-3)
sql::Connection *con			- the database connection object
double ingame_clock_time		- the internal counter for the ingame clock's time
string *current_time			- the current time in SQL TIME format
bool *force_resync				- the boolean to force a resync if an API error occurs

This function takes an entire map's worth of objectives and stores each objective-state as activity_data
	in the database. It calls update_activityData to set previous activity_datas' duration_owned and
		duration_claimed attributes.
*/
void store_activityData(const Json::Value *match_data, int mapNum, sql::Connection *con, double ingame_clock_time, string *current_time, bool *force_resync)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "";
	const Json::Value objectives = (*match_data)["maps"][mapNum]["objectives"]; //an array of objective data
	for (int i = 0; i < (int)objectives.size(); i++) //iterate through each object in the objective array
	{
		update_activityData(match_data,&objectives[i],con,current_time); 
		//update the previous row (if any) of the objective-data in the database ...
		// ... to have duration_owned and duration_claimed
		//begin SQL string building
			SQLstmt = "INSERT INTO activity_data VALUES(";						
			SQLstmt += "\"" + objectives[i]["last_flipped"].asString() + "\"";
			SQLstmt += ",\"" + objectives[i]["id"].asString() + "\",";
			//
			if (objectives[i]["owner"].asString() == "Red")
			{
				convertNumToString(&converter,(*match_data)["worlds"][THIRD_SRV].asInt(),&SQLstmt);
			}
			else if (objectives[i]["owner"].asString() == "Blue")
			{
				convertNumToString(&converter,(*match_data)["worlds"][SECOND_SRV].asInt(),&SQLstmt);
			}
			else if (objectives[i]["owner"].asString() == "Green")
			{
				convertNumToString(&converter,(*match_data)["worlds"][FIRST_SRV].asInt(),&SQLstmt);
			}
			else
			{
				SQLstmt += "0"; //neutral-server
			}
			check_guildClaim(objectives[i]["claimed_by"].asString(),con,force_resync); 
				//add the guild that claimed the objective to the database if it doesn't exist yet
			SQLstmt += ",\"" + objectives[i]["claimed_by"].asString() + "\",";
			convertNumToString(&converter,ingame_clock_time,&SQLstmt);
			SQLstmt += ",\"" + objectives[i]["owner"].asString() + "\"";
			SQLstmt += ",\"" + objectives[i]["claimed_at"].asString() + "\"";
			SQLstmt += ",\"" + (*match_data)["id"].asString() + "\"";
			SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
			SQLstmt += ",NULL, NULL"; //duration_ owned/claimed; updated at a later time
			SQLstmt += ",\"" + (*current_time);
			SQLstmt += "\");";
		//end SQL string building
		try
		{
			stmt = con->createStatement();
			stmt->execute(SQLstmt); //execute the statement to store the data into the database
		}
		catch (sql::SQLException &e)
		{
			//cout << e.what() << endl;
		}
		delete stmt;
	}
}
/*
int grn_srv, blu_srv, red_srv	- the 4-digit server id's for each server in a given matchup
string *SQLstmt					- the SQLstmt to append the server populations to
bool *force_resync				- if an API error occurs, force a resync in the main body

This function queries the API for the three given server's populations and appends the population data 
	to the SQLstmt
*/
void get_server_populations(int grn_srv, int blu_srv, int red_srv, string *SQLstmt, bool *force_resync)
{
	if (grn_srv == 0 && blu_srv == 0 && red_srv == 0)
	{ //if all server ids are 0 (due to bad data), return early.
		(*SQLstmt) += ",NULL,NULL,NULL"; //append NULL data to the SQL string
		return;
	}
	Easy request;
	stringstream result;
	Json::Value server_populations;
	Json::Reader parser;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&result));
	string requestURL = "https://api.guildwars2.com/v2/worlds?ids=";
	convertNumToString(&result,grn_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,blu_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,red_srv,&requestURL);
	request.setOpt(Url(requestURL)); //make a request to the API for world-info on the 3 given servers
	try
	{
		request.perform();
		/* */
		if (parser.parse(result.str(), server_populations))
		{ //parse the data from the API
			(*SQLstmt) += ",\"";			
			for (unsigned int i = 0; i < server_populations.size(); i++)
			{
				if (server_populations[i]["id"].asInt() == grn_srv)
				{ //properly "align" the data to store the green server population first
					(*SQLstmt) += server_populations[i]["population"].asString();
					break;
				}
			}
			(*SQLstmt) += "\"";
			(*SQLstmt) += ",\"";
			for (unsigned int i = 0; i < server_populations.size(); i++)
			{
				if (server_populations[i]["id"].asInt() == blu_srv)
				{ //properly "align" the data to store the blue server population second
					(*SQLstmt) += server_populations[i]["population"].asString();
					break;
				}
			}
			(*SQLstmt) += "\"";
			(*SQLstmt) += ",\"";
			for (unsigned int i = 0; i < server_populations.size(); i++)
			{
				if (server_populations[i]["id"].asInt() == red_srv)
				{ //properly "align" the data to store the red server population last
					(*SQLstmt) += server_populations[i]["population"].asString();
					break;
				}
			}
			(*SQLstmt) += "\"";
		}
	}
	catch (exception &e)
	{
		cout << e.what() << endl;
		(*force_resync) = true;
	}
}
/*
string *weekNum		- the string to append the week number to
string match_time	- the given match's start time

This function takes a given match's start_time, computes the difference in time to the
	beginning of the year, in number of weeks, and appends that value to weekNum
*/
void get_weekNumber(string *weekNum, string match_time)
{
	struct tm * matchTime_tm;
	char weekNumber[3]; //2 characters + the null-character
	//
	struct tm match_start;
	strptime(match_time.c_str(), "%Y-%m-%dT%H:%M:%SZ", &match_start); //parses a string into a 'tm' struct
	time_t t = mktime(&match_start); //converts a 'tm' struct to a time_t
	matchTime_tm = localtime(&t); //converts given time to local time
	strftime (weekNumber,3,"%U",matchTime_tm); //get the weeknumber and store it in the character array
	(*weekNum) = weekNumber; //append the week number to the return-string
}
/*
const Json::Value *match_data	- The trunk of all match data
string region					- The region (1 for NA, 2 for EU) to store data for
sql::Connection *con			- The database connection object
bool *force_resync				- If an API error occurs, force a resync in the main body

This function takes an entire region's worth of match_data and stores the weekly details into the database as match_details
It calls get_weekNumber and get_server_populations.
*/
void store_matchDetails(const Json::Value *match_data, string region, sql::Connection *con, bool *force_resync)
{
	stringstream converter;
	sql::Statement *stmt;
	sql::ResultSet *res;
	string SQLstmt;
	string weekNum;
	stmt = con->createStatement();
	//initialize some variables
	for (int i = 0; i < (int)(*match_data).size(); i++)
	{ //iterate through each set of match_data individually
		if ((((*match_data)[i]["id"]).asString())[0] == region[0])
		{ //only store data for the given region
			try
			{ //check if the data for the given matchup already exists in the database
				res = stmt->executeQuery("SELECT * FROM match_details WHERE match_id = \"" + (*match_data)[i]["id"].asString() + "\" and start_time =\"" + (*match_data)[i]["start_time"].asString() + "\";");
			} //if it already exists, this iteration will exit without modifying the database
			catch (sql::SQLException &e)
			{
				cout << e.what() << endl;
			}
			if (!res->next()) //if this set of match_details does not already exist in the DB
			{
				get_weekNumber(&weekNum, (*match_data)[i]["start_time"].asString()); //get the week number of the match
				//begin SQL string building
					SQLstmt = "INSERT INTO match_details VALUES(";
					SQLstmt += "\"" + (*match_data)[i]["id"].asString() + "\"";
					SQLstmt += ",\"" + weekNum + "\"";
					SQLstmt += ",\"" + (*match_data)[i]["start_time"].asString() + "\"";
					SQLstmt += ",\"" + (*match_data)[i]["end_time"].asString() + "\",";
					convertNumToString(&converter, (*match_data)[i]["worlds"][FIRST_SRV].asInt(),&SQLstmt);
					SQLstmt += ",";
					convertNumToString(&converter, (*match_data)[i]["worlds"][SECOND_SRV].asInt(),&SQLstmt);
					SQLstmt += ",";
					convertNumToString(&converter, (*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt);
					get_server_populations((*match_data)[i]["worlds"][FIRST_SRV].asInt(),(*match_data)[i]["worlds"][SECOND_SRV].asInt(),(*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt,force_resync);
					SQLstmt += ",";
					int red2id=0,blu2id=0,grn2id=0;
					if ((*match_data)[i]["all_worlds"][FIRST_SRV][0].asInt() == (*match_data)[i]["worlds"][FIRST_SRV].asInt())
					{
						SQLstmt += "NULL";
					}
					else
					{
						convertNumToString(&converter, (*match_data)[i]["all_worlds"][FIRST_SRV][0].asInt(),&SQLstmt);
						grn2id=(*match_data)[i]["all_worlds"][FIRST_SRV][0].asInt();
					}
					SQLstmt += ",";
					if ((*match_data)[i]["all_worlds"][SECOND_SRV][0].asInt() == (*match_data)[i]["worlds"][SECOND_SRV].asInt())
					{
						SQLstmt += "NULL";
					}
					else
					{
						convertNumToString(&converter, (*match_data)[i]["all_worlds"][SECOND_SRV][0].asInt(),&SQLstmt);
						blu2id=(*match_data)[i]["all_worlds"][SECOND_SRV][0].asInt();
					}
					SQLstmt += ",";	
					if ((*match_data)[i]["all_worlds"][THIRD_SRV][0].asInt() == (*match_data)[i]["worlds"][THIRD_SRV].asInt())
					{
						SQLstmt += "NULL";
					}
					else
					{
						convertNumToString(&converter, (*match_data)[i]["all_worlds"][THIRD_SRV][0].asInt(),&SQLstmt);
						red2id=(*match_data)[i]["all_worlds"][THIRD_SRV][0].asInt();
					}
					get_server_populations(grn2id,blu2id,red2id,&SQLstmt,force_resync);
					SQLstmt += ");";
				//end SQL string building
				try
				{ //execute the SQL statement to store the match details
					stmt->execute(SQLstmt);
				}
				catch (sql::SQLException &e)
				{
					cout << e.what() << endl;
				}
			}
			try
			{
				delete res; //attempt the delete the resultset before iterating again
			} //may fail if no data was returned to begin with
			catch (exception &e)
			{ //the try-catch lets the iterating continue anyway
				cout << e.what() << endl;
			}
		}
	}
	delete stmt;
}
/*
const Json::Value *match_data	- The trunk of match data for a single match
int mapNum						- The index of the map to check the ppt (point per tick) value
string *SQLstmt					- The SQL string to append the data to
stringstream *converter			- The stringstream to use for converting numbers to strings
sql::Connection *con			- The database connection object

This function iterates through each objective on the given map for a given match and totals up
	the green, blue and red ppt (point per tick) values. These values are appended the SQLstmt
*/
void get_server_ppt(const Json::Value *match_data, int mapNum, string *SQLstmt, stringstream *converter, sql::Connection *con)
{
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement();
	int green_ppt = 0, blue_ppt = 0, red_ppt = 0; //initialize each ppt value to 0
	for (int i = 0; i < (int)(*match_data)["maps"][mapNum]["objectives"].size(); i++)
	{ //iterate through each objective on the map
		try
		{
			res = stmt->executeQuery("SELECT ppt_value FROM objective WHERE obj_id = \"" + (*match_data)["maps"][mapNum]["objectives"][i]["id"].asString() + "\";");
			res->next(); //query the database for the objective's (not activity_data) ppt value
			if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Green")
			{ //if the current owner is green, add the objective's ppt to green_ppt
				green_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Blue")
			{ //if the current owner is blue, add the objective's ppt to green_ppt
				blue_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Red")
			{ //if the current owner is red, add the objective's ppt to green_ppt
				red_ppt += res->getInt("ppt_value");
			}
			delete res; //free the memory inside the loop!
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;	
		}
	}
	//append the total ppt values for each color to the SQLstmt
	(*SQLstmt) += ",";
	convertNumToString(converter,green_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,blue_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,red_ppt,SQLstmt);
	delete stmt;
}
/*
string data		- The data to append
string *SQLstmt	- the string to append to

This function simply adds a "0" if the data string is empty; otherwise it just
	concatenates two strings.
*/
void append_server_stats(string data, string *SQLstmt)
{
	if (data == "")
	{
		data = "0";
	}
	(*SQLstmt) += data;
}
/*
const Json::Value *match_data	- The trunk of match data for a single match
int mapNum						- The index of the map number (0-3)
sql::Connection *con			- The database connection object
string *current_time			- The current timeStamp in SQL TIME format

This function stores an individual map's score, kills, deaths and ppt totals, for each color, in a specific matchup, into the database
It calls get_server_ppt
*/
void store_mapScores(const Json::Value *match_data, int mapNum, sql::Connection *con, string *current_time)
{
	stringstream converter;
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement();
	bool errorCorrected = false; //initially set to false; only true if previous map_score data is less than new data
    //begin SQL string building
		string SQLstmt = "INSERT INTO map_scores VALUES(\"";
	   	SQLstmt += (*current_time);
		SQLstmt += "\",\"" + (*match_data)["id"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["maps"][mapNum]["type"].asString() + "\",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][FIRST_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][SECOND_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][THIRD_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		string start_time = (*match_data)["start_time"].asString();
		start_time[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
		start_time.erase(19,1); // ^^
	//end SQL string building
	res = stmt->executeQuery("SELECT timeStamp, "FIRST_SRV"Kills, "SECOND_SRV"Kills, "THIRD_SRV"Kills, "FIRST_SRV"Deaths, "SECOND_SRV"Deaths, "THIRD_SRV"Deaths FROM map_scores WHERE match_id = \"" + (*match_data)["id"].asString() + "\" and map_id = \"" + (*match_data)["maps"][mapNum]["type"].asString() + "\" and start_time = \"" + start_time + "\" and error_corrected = 0 ORDER BY timeStamp DESC LIMIT 1;");
	//query the database for the previous set of score/kill/death data for the given matchup and map
	int firstKills,firstDeaths,secondKills,secondDeaths,thirdKills,thirdDeaths;
	firstKills = ((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt());
	secondKills = ((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt());
	thirdKills = ((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt());
	firstDeaths = ((*match_data)["maps"][mapNum]["deaths"][FIRST_SRV].asInt());
	secondDeaths = ((*match_data)["maps"][mapNum]["deaths"][SECOND_SRV].asInt());
	thirdDeaths = ((*match_data)["maps"][mapNum]["deaths"][THIRD_SRV].asInt());
	//initialize these variables to the current data
	if (res->next())
	{ //if there was a previous data record with the specified restrictions
		if (res->getInt(FIRST_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt()) || res->getInt(SECOND_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt()) || res->getInt(THIRD_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt()))
		{ //check to see if the previous data is greater than the new data. if it is...
			//API data-error detected
			//sum the previous data with the new data to get an accurate count
			firstKills += res->getInt(FIRST_SRV"Kills");
			secondKills += res->getInt(SECOND_SRV"Kills");
			thirdKills += res->getInt(THIRD_SRV"Kills");
			firstDeaths += res->getInt(FIRST_SRV"Deaths");
			secondDeaths += res->getInt(SECOND_SRV"Deaths");
			thirdDeaths += res->getInt(THIRD_SRV"Deaths");
			errorCorrected = true; //mark the new row as error corrected
		}
  	}
  	//more SQL string building
	convertNumToString(&converter,firstKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,secondKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,thirdKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,firstDeaths,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,secondDeaths,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,thirdDeaths,&SQLstmt);
    get_server_ppt(match_data, mapNum, &SQLstmt, &converter, con);
    //get the ppt value for each color on this map
	if (errorCorrected == true)
    { //if API-errors were corrected, mark the row as such
   		SQLstmt += ",1";
    }
    else
    {
    	SQLstmt += ",0";
    }
    SQLstmt += ");";
    try
	{ //store the data into the database
		stmt->execute(SQLstmt);
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
	delete res;
	delete stmt; //free the memory used by the SQL objects
}
/* 
string region				- The region to obtain data for (1 for NA, 2 for EU)
sql::Connection *con		- The database connection object
double ingame_clock_time	- The internal counter representing the ingame clock time
bool *stored_matchDetails	- Determines if this week's match details have been stored yet or not
								-> resets when region start_time differs
bool *force_resync			- Force a resync in the main body if an API error occurs
string *previous_start_time	- The previous start_time for the region; used to determine when to store new match_details

This function queries the Gw2 API for all match data, constructs a timeStamp to be used in other functions, and calls
	other functions to store the data, passing along the 'global' booleans, previous_time, and other required info.
	
Calls store_matchDetails (once a week), store_activityData (once a TIME_RES), and store_mapScores (once every 15 minutes)
*/
void get_matchDetails(string region, sql::Connection *con, double ingame_clock_time, bool *stored_matchDetails, bool *force_resync, string *previous_start_time)
{ //region: 1 = NA, 2 = EU
	Easy request;
	stringstream matchDetails,converter;
	Json::Value all_match_data;
	Json::Reader parser;
	struct tm * UTCTime;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	request.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches?ids=all")); //gets every matchup from the API
	//get the current time in UTC format to pass later
	time_t t = time(NULL); //get current local time
	UTCTime = gmtime( &t ); //convert current time to UTC
	//begin string processing to create a SQL TIME formatted timeStamp
		string current_time = "";
		convertNumToString(&converter,(UTCTime->tm_year + 1900),&current_time);
		current_time += "-";
		convertNumToString(&converter,(UTCTime->tm_mon + 1),&current_time);
		current_time += "-";
		convertNumToString(&converter,(UTCTime->tm_mday),&current_time);
		current_time += " ";
		convertNumToString(&converter,(UTCTime->tm_hour),&current_time);
		current_time += ":";
		convertNumToString(&converter,(UTCTime->tm_min),&current_time);
		current_time += ":";
		convertNumToString(&converter,(UTCTime->tm_sec),&current_time);
	//end string processing
	try
	{
		request.perform();
		/* */
		if (parser.parse(matchDetails.str(), all_match_data))
		{ //if the data returned from the API can be parsed
			if (!(*stored_matchDetails))
			{ //if the matchDetails haven't been stored for this week
				store_matchDetails(&all_match_data, region, con, force_resync); //store the info
				for (int k = 0; k < (int)all_match_data.size(); k++)
				{ //iterate through all match data sets
					if ((all_match_data[k]["id"].asString())[0] == region[0]) //filter down to matches in the region's range
					{ //find the first match data set that is in the desired region
						(*previous_start_time) = (all_match_data[k]["start_time"].asString());
						break; //set the previous_start_time to that regions' start time, and exit out of the loop
					}
				}
				(*stored_matchDetails) = true; //the match details have been stored for this week
			}
			for (int j = 0; j < (int)all_match_data.size(); j++)
			{ //loop through all match data sets
				if ((all_match_data[j]["id"].asString())[0] == region[0])
				{ //only process matc data sets in the specified region
					for (int i = 0; i < (int)all_match_data[j]["maps"].size(); i++)
					{ //loop through each map of each match data set
						store_activityData(&all_match_data[j], i, con, ingame_clock_time, &current_time, force_resync);
						//store the activity_data for each map of each match
						if (ingame_clock_time == 14)
						{ //only store mapscore data every point-tally in-game
							store_mapScores(&all_match_data[j], i, con, &current_time);
						}
					}
				}
			}
		}
	}
	catch (exception &e)
	{
		(*force_resync) = true;
		cout << e.what() << endl;
	}
}
/*
string region				- The region to sync to (1 for NA, 2 for EU)
double timeToSleep			- The time (in microseconds) to sleep initially
bool *stored_matchDetails	- Whether or not the match details for the week have been stored yet.
								-> Reset when the new start_time is not equal to the previous_start_time
string *previous_start_time	- The previously recorded start_time for the week's matches

This function synchronizes to the in-game clock by comparing a match's total score (red+blue+green) every 5 seconds.
It loops indefinitely until it has synchronized to the in-game clock.
When the current score is 635 (or more) above the previous score, a point-tick has occurred in the game, and this function exits.
*/
void sync_to_ingame_clock(string region, double timeToSleep, bool *stored_matchDetails, string *previous_start_time) //1 = NA, 2 = EU
{
	Easy request;
	stringstream matchDetails;
	Json::Value score_data;
	Json::Reader parser;
	int previousScore = 9999999, currentScore = 0; //initialize to very high value
	/* */
	string match_url = "https://api.guildwars2.com/v2/wvw/matches/" + region + "-1";
	//obtain the first match from the specified region. An entire region's matches
		//share the same in-game clock
	request.setOpt(Url(match_url));
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	if (timeToSleep < 0)
	{ //if there is a negative value for timeToSleep, set it to 0 instead
		timeToSleep = 0;
	}
	usleep(timeToSleep); //wait some seconds to reduce the number of API calls made
	while (1)
	{ //loop indefinitely; a break is called when it has synchronized
		try
		{
			request.perform(); //perform the same request every loop
			if (parser.parse(matchDetails.str(), score_data))
			{ //parse the data retrieved from the API
				cout << "Syncing ..." << region << endl;
				if ((*stored_matchDetails) && score_data["start_time"].asString() != (*previous_start_time))
				{ //if the new start_time for the region is not the same as the previous start_time, then we need
					//to store a new set of match_details after this function exits
					cout << "NEW MATCH!" << endl;
					(*stored_matchDetails) = false;
				}
				currentScore = score_data["scores"][FIRST_SRV].asInt() + score_data["scores"][SECOND_SRV].asInt() + score_data["scores"][THIRD_SRV].asInt();
				//total up the current score, from all three servers combined
				if (currentScore >= (previousScore+635))
				{ //if the current score is 635 (or more) over the previous score
					break; //then the in-game clock is 14:00 and we've synchronized
				}
				previousScore = currentScore; //if we haven't synchronized yet, update the previousScore with the current
			}
			else
			{
				cout << "Error parsing data in sync-loop!" << endl;
			}
			matchDetails.str(""); //clear the stringstream for the next API request
			matchDetails.clear();
		}
		catch (exception &e)
		{
			cout << e.what() << endl;
		}
		/* */
		usleep(MICROSEC*5); //sleep for 5 seconds
	}
	/* */
}
/*
void *ptr	- A single character which is either 1 or 2, denoting the region number
				-> This void *ptr type is required for threads to pass along arguments
				
This function is the main body of the program.
First, it connects to the database and creates a *sql::Connection object to pass to each function as needed
Second, it does an initial synchronization to the in-game clock, to start the internal-counter correctly.
After that, it enters an infinite loop.
	It calls get_matchDetails, which is the single entry point to collect, process, and store data
	It calculates the time it took to perform the above, to be used when idling at the end of the loop
	It determines if it should resync, either due to an error contacting the API or due to taking too long to process data
	It then determines if it should resychronize to the in-game clock (every 15 minutes), or if the internal counter needs to wrap back to 15
	It subtracts one TIME_RES (default 60 seconds) from the interna clock
	Then it idles for the determined time, which, combined with processing time, is equal to TIME_RES (default 60 seconds)
*/
void *collect_data(void *ptr) //1 = North American, 2 = European
{
	try 
    {
    	//reconstruct the region into a string to pass to functions as needed
			const char *args = (char *) ptr;
			string region = "";
			region += args;
    	//Connect to the database and create the sql::Connection *con object
			sql::mysql::MySQL_Driver *driver;
			sql::Connection *con;
			sql::Statement *stmt;
			driver = sql::mysql::get_mysql_driver_instance();
			con = driver->connect(IPADDR, USERNAME, PASSWORD);
			stmt = con->createStatement();
			stmt->execute("USE " DATABASE);
			delete stmt;
		//initialize some variables to be used in the collection loop
			time_t beginTime, endTime;
			double elapsed_msecs;
			double ingame_clock_time = 14.0*60.0;
			bool stored_matchDetails = false;
			bool force_resync = false;
			string previous_start_time = "";
		//synchronize to the in game clock initially to ensure the internal counter is correct
		sync_to_ingame_clock(region,0,&stored_matchDetails,&previous_start_time);
    	while (1)
		{ //loop indefinitely
			beginTime = time(0); //get the current time
			cout << "Beginning " << ingame_clock_time/60.0 << "| region " << region << endl;
			get_matchDetails(region, con, ingame_clock_time/60.0,&stored_matchDetails,&force_resync,&previous_start_time);
			//collect, process, and store data
			cout << "Ending " << ingame_clock_time/60.0 << "| region " << region << endl;
			endTime = time(0); //get the current time after processing the data
			elapsed_msecs = difftime(endTime, beginTime) * MICROSEC; //determine how long the loop should idle for at the end
			cout << elapsed_msecs/MICROSEC << " seconds elapsed" << "| region " << region<< endl;
			cout << "Time to sleep: " << (double)(MICROSEC*TIME_RES - elapsed_msecs)/MICROSEC << "| region " << region << endl;
			if (elapsed_msecs/MICROSEC > TIME_RES || force_resync)
			{ //if data processing took too long, or there was an issue contacting the API, resynchronize immediately
				cout << "Too much time elapsed! Resyncing" << "| region " << region << endl;
				ingame_clock_time = 15*60.0; //by setting the ingame_clock_time to 15 minutes, the if-else below will resync for us
				force_resync = false; //no longer need to force a resync
			}
			if (ingame_clock_time/60 == 15)
			{ //if the in-game clock time is 15:00, then in the next 60 seconds, a point-tick will occur and we need to resync
				cout << "Sync-wait: " << (MICROSEC*0.60*TIME_RES - elapsed_msecs)/MICROSEC << "| region " << region << endl;
				sync_to_ingame_clock(region,MICROSEC*0.60*TIME_RES - elapsed_msecs,&stored_matchDetails,&previous_start_time); //resync to in-game clock every cycle
				//resynchronize to the in-game clock
				elapsed_msecs = MICROSEC*TIME_RES-1; //set the time to sleep to (effectively) 0 seconds
			}
			else if (ingame_clock_time <= TIME_RES)
			{ //if the ingame_clock_time is less than one TIME_RES (default 60 seconds) then...
				ingame_clock_time = 15*60.0 + TIME_RES; //... set TIME_RES to 15 minutes + TIME_RES because 1 TIME_RES is subtracted below
			}
			ingame_clock_time -= TIME_RES; //subtract one TIME_RES off of the internal clock (default 60 seconds)
			usleep((double)(MICROSEC*TIME_RES - elapsed_msecs)); //sleep for the determined time
		}
		delete con; //technically this is never reached... can't delete it prior, as it is used to create and execute statements
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
	return NULL; //required for multi-threading to function as determined by the compiler; never actually reached
}
/*
This function creates two threads - one for NA, one for EU - and starts collecting data for each.

Calls collect_data
*/
int main (int argc, char *argv[])
{
	pthread_t region1, region2;
	const char *args1 = "1";
	const char *args2 = "2";
	pthread_create( &region1, NULL, collect_data, (void*) args1);
	pthread_create( &region2, NULL, collect_data, (void*) args2);
	pthread_join( region1, NULL);
	pthread_join( region2, NULL);
	return 0;
}
